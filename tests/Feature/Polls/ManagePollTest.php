<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\Notifications\Mail\FinalDateMail;
use App\Modules\Polls\Actions\SubmitVote;
use App\Modules\Polls\Enums\PollStatus;
use App\Modules\Polls\Models\Participant;
use App\Modules\Polls\Models\Poll;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

function grantManage(Poll $poll, string $token): void
{
    $response = test()->get("/u/{$poll->public_id}/verwalten/{$token}")->assertRedirect("/u/{$poll->public_id}/verwalten");
    carryCookies($response, 'zf_manage');
}

it('opens the management page via the admin link', function () {
    [$poll, $token] = createPoll();

    grantManage($poll, $token);

    $this->get("/u/{$poll->public_id}/verwalten")->assertOk()->assertSee('Finalen Termin festlegen');
});

it('forbids management without the admin link', function () {
    [$poll] = createPoll();

    $this->get("/u/{$poll->public_id}/verwalten")->assertForbidden();
    $this->put("/u/{$poll->public_id}/verwalten", ['title' => 'Gehackt'])->assertForbidden();
    $this->post("/u/{$poll->public_id}/festlegen", ['option_id' => $poll->options->first()->id])->assertForbidden();
    $this->delete("/u/{$poll->public_id}")->assertForbidden();
    expect($poll->refresh()->title)->toBe('Teamessen');
});

it('rejects wrong admin tokens', function (string $token) {
    [$poll] = createPoll();

    $this->get("/u/{$poll->public_id}/verwalten/".rawurlencode($token))->assertNotFound();
})->with([str_repeat('a', 64), "' OR '1'='1", 'kurz']);

it('does not grant management of another poll with an admin link', function () {
    [$poll, $token] = createPoll();
    [$other] = createPoll();

    $this->get("/u/{$other->public_id}/verwalten/{$token}")->assertNotFound();
});

it('lets signed-in owners manage without the link', function () {
    $user = User::factory()->create();
    [$poll] = createPoll([], $user);

    $this->actingAs($user)->get("/u/{$poll->public_id}/verwalten")->assertOk();
    $this->actingAs(User::factory()->create())->get("/u/{$poll->public_id}/verwalten")->assertForbidden();
});

it('updates details and adds and removes options', function () {
    [$poll, $token] = createPoll();
    grantManage($poll, $token);
    $remove = $poll->options->first();

    $this->put("/u/{$poll->public_id}/verwalten", [
        'title' => 'Teamessen 2.0',
        'location' => '',
        'allow_maybe' => '0',
        'notify_on_vote' => '1',
        'remove_options' => [$remove->id],
        'new_options' => [['date' => '2030-06-01', 'start' => '12:00', 'end' => '13:00']],
    ])->assertRedirect("/u/{$poll->public_id}/verwalten");

    $poll->refresh();
    expect($poll->title)->toBe('Teamessen 2.0')
        ->and($poll->allow_maybe)->toBeFalse()
        ->and($poll->notify_on_vote)->toBeTrue()
        ->and($poll->options()->count())->toBe(3)
        ->and($poll->options()->whereKey($remove->id)->exists())->toBeFalse();
});

it('keeps at least one option', function () {
    [$poll, $token] = createPoll();
    grantManage($poll, $token);

    $this->put("/u/{$poll->public_id}/verwalten", [
        'title' => 'X',
        'remove_options' => $poll->options->pluck('id')->all(),
    ])->assertSessionHasErrors('new_options');

    expect($poll->options()->count())->toBe(3);
});

it('finalizes the poll and notifies all participants with a calendar file', function () {
    [$poll, $token] = createPoll();
    app(SubmitVote::class)->create($poll, 'Max', 'max@example.com', []);
    app(SubmitVote::class)->create($poll, 'Lisa', 'lisa@example.com', []);
    grantManage($poll, $token);
    $option = $poll->options->last();

    $this->post("/u/{$poll->public_id}/festlegen", ['option_id' => $option->id])->assertRedirect();

    $poll->refresh();
    expect($poll->status)->toBe(PollStatus::Finalized)->and($poll->final_option_id)->toBe($option->id);

    foreach (['max@example.com', 'lisa@example.com', 'erika@example.com'] as $email) {
        Mail::assertSent(FinalDateMail::class, fn (FinalDateMail $mail) => $mail->hasTo($email)
            && str_contains($mail->ics, 'BEGIN:VEVENT') && str_contains($mail->ics, 'SUMMARY:Teamessen'));
    }
    Mail::assertSent(FinalDateMail::class, 3);

    $this->get("/u/{$poll->public_id}")->assertSee('Der Termin steht fest')->assertDontSee('Wann kannst du?');
    $this->get("/u/{$poll->public_id}/termin.ics")->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertSee('DTSTART:20300503T073000Z');
});

it('rejects finalizing with an option of another poll', function () {
    [$poll, $token] = createPoll();
    [$other] = createPoll();
    grantManage($poll, $token);

    $this->post("/u/{$poll->public_id}/festlegen", ['option_id' => $other->options->first()->id])->assertNotFound();
    expect($poll->refresh()->isOpen())->toBeTrue();
});

it('has no calendar file before finalizing', function () {
    [$poll] = createPoll();

    $this->get("/u/{$poll->public_id}/termin.ics")->assertNotFound();
});

it('reopens a finalized poll', function () {
    [$poll, $token] = createPoll();
    grantManage($poll, $token);
    $this->post("/u/{$poll->public_id}/festlegen", ['option_id' => $poll->options->first()->id]);

    $this->post("/u/{$poll->public_id}/wieder-oeffnen")->assertRedirect();

    expect($poll->refresh()->isOpen())->toBeTrue()->and($poll->final_option_id)->toBeNull();
});

it('removes a participant, but only of its own poll', function () {
    [$poll, $token] = createPoll();
    [$other] = createPoll();
    [$mine] = app(SubmitVote::class)->create($poll, 'Max', 'max@example.com', []);
    [$foreign] = app(SubmitVote::class)->create($other, 'Lisa', 'lisa@example.com', []);
    grantManage($poll, $token);

    $this->delete("/u/{$poll->public_id}/teilnehmende/{$foreign->id}")->assertNotFound();
    $this->delete("/u/{$poll->public_id}/teilnehmende/{$mine->id}")->assertRedirect();

    expect(Participant::query()->pluck('id')->all())->toBe([$foreign->id]);
});

it('shows participant emails to the organizer', function () {
    [$poll, $token] = createPoll();
    app(SubmitVote::class)->create($poll, 'Max', 'max@example.com', []);
    grantManage($poll, $token);

    $this->get("/u/{$poll->public_id}/verwalten")->assertSee('max@example.com');
});

it('deletes the poll', function () {
    [$poll, $token] = createPoll();
    app(SubmitVote::class)->create($poll, 'Max', 'max@example.com', []);
    grantManage($poll, $token);

    $this->delete("/u/{$poll->public_id}")->assertRedirect('/');

    expect(Poll::query()->count())->toBe(0)->and(Participant::query()->count())->toBe(0);
});

it('writes changes to the activity log without tokens', function () {
    [$poll, $token] = createPoll();
    grantManage($poll, $token);
    $this->post("/u/{$poll->public_id}/festlegen", ['option_id' => $poll->options->first()->id]);

    $actions = ActivityLog::query()->pluck('action')->all();
    expect($actions)->toContain('poll.created', 'poll.finalized');
    expect(ActivityLog::query()->get()->toJson())->not->toContain($token);
});
