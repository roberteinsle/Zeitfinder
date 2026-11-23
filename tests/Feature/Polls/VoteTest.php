<?php

use App\Models\User;
use App\Modules\Notifications\Mail\NewVoteMail;
use App\Modules\Notifications\Mail\VoteReceiptMail;
use App\Modules\Polls\Actions\FinalizePoll;
use App\Modules\Polls\Enums\Answer;
use App\Modules\Polls\Models\Participant;
use App\Modules\Polls\Models\Vote;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

function answersFor($poll, array $values): array
{
    $answers = [];
    foreach ($poll->options as $i => $option) {
        $answers[$option->id] = $values[$i] ?? 'no';
    }

    return $answers;
}

it('shows the poll page without an account', function () {
    [$poll] = createPoll();

    $this->get("/u/{$poll->public_id}")->assertOk()->assertSee('Teamessen')->assertSee('Wann kannst du?');
});

it('returns 404 for unknown or malformed poll ids', function () {
    $this->get('/u/abcdefghijkl')->assertNotFound();
    $this->get("/u/' OR '1'='1")->assertNotFound();
});

it('records a vote and sends an edit link', function () {
    [$poll] = createPoll();

    $this->post("/u/{$poll->public_id}/antwort", [
        'name' => 'Max Mustermann',
        'email' => 'Max@Example.com',
        'answers' => answersFor($poll, ['yes', 'maybe', 'no']),
    ])->assertRedirect("/u/{$poll->public_id}#ergebnis")->assertCookie('zf_votes');

    $participant = Participant::query()->sole();
    expect($participant->email)->toBe('max@example.com')
        ->and($participant->votes()->pluck('answer')->map->value->sort()->values()->all())->toBe(['maybe', 'no', 'yes']);

    Mail::assertSent(VoteReceiptMail::class, fn (VoteReceiptMail $mail) => $mail->hasTo('max@example.com')
        && preg_match('#/u/'.$poll->public_id.'/antwort/[0-9a-f]{64}$#', $mail->editUrl) === 1);
});

it('treats missing answers as no', function () {
    [$poll] = createPoll();

    $this->post("/u/{$poll->public_id}/antwort", ['name' => 'Max', 'email' => 'max@example.com']);

    expect(Vote::query()->pluck('answer')->unique()->all())->toEqual([Answer::No]);
});

it('prevents a second vote with the same email and mails the owner a fresh edit link', function () {
    [$poll] = createPoll();
    $this->post("/u/{$poll->public_id}/antwort", ['name' => 'Max', 'email' => 'max@example.com', 'answers' => answersFor($poll, ['yes'])]);
    $oldHash = Participant::query()->sole()->edit_token_hash;

    // Another browser (cookies are not carried over) tries the same address.
    $response = $this->post("/u/{$poll->public_id}/antwort", ['name' => 'Fake', 'email' => ' MAX@example.com', 'answers' => answersFor($poll, ['no'])]);

    $response->assertSessionHasErrors('email');
    expect(Participant::query()->count())->toBe(1)
        ->and(Participant::query()->sole()->name)->toBe('Max')
        ->and(Participant::query()->sole()->edit_token_hash)->not->toBe($oldHash);
    Mail::assertSent(VoteReceiptMail::class, fn (VoteReceiptMail $mail) => $mail->duplicate && $mail->hasTo('max@example.com'));
});

it('lets the participant change the answer from the same browser', function () {
    [$poll] = createPoll();
    $response = $this->post("/u/{$poll->public_id}/antwort", ['name' => 'Max', 'email' => 'max@example.com', 'answers' => answersFor($poll, ['yes', 'yes', 'yes'])]);
    carryCookies($response, 'zf_votes');

    $this->get("/u/{$poll->public_id}")->assertSee('Deine Antwort ändern');
    $this->put("/u/{$poll->public_id}/antwort", ['name' => 'Max M.', 'answers' => answersFor($poll, ['no', 'no', 'maybe'])])
        ->assertRedirect();

    $participant = Participant::query()->sole();
    expect($participant->name)->toBe('Max M.')
        ->and($participant->votes()->where('answer', 'yes')->count())->toBe(0);
});

it('lets the participant change the answer via the edit link', function () {
    [$poll] = createPoll();
    $this->post("/u/{$poll->public_id}/antwort", ['name' => 'Max', 'email' => 'max@example.com']);
    $editUrl = null;
    Mail::assertSent(VoteReceiptMail::class, function ($mail) use (&$editUrl) {
        $editUrl = $mail->editUrl;

        return true;
    });

    // A new browser opens the edit link and may then change the answer.
    $this->get("/u/{$poll->public_id}")->assertSee('Wann kannst du?');
    $claim = $this->get((string) parse_url($editUrl, PHP_URL_PATH))->assertRedirect("/u/{$poll->public_id}")->assertCookie('zf_votes');
    carryCookies($claim, 'zf_votes');
    $this->get("/u/{$poll->public_id}")->assertSee('Deine Antwort ändern');
});

it('rejects wrong edit tokens', function () {
    [$poll] = createPoll();
    $this->post("/u/{$poll->public_id}/antwort", ['name' => 'Max', 'email' => 'max@example.com']);

    $this->get("/u/{$poll->public_id}/antwort/".str_repeat('a', 64))->assertNotFound();
    $this->get("/u/{$poll->public_id}/antwort/".rawurlencode("' OR '1'='1"))->assertNotFound();
});

it('forbids updating without being the participant', function () {
    [$poll] = createPoll();

    $this->put("/u/{$poll->public_id}/antwort", ['name' => 'Max', 'answers' => []])->assertForbidden();
});

it('recognises signed-in users by their account', function () {
    $user = User::factory()->create(['email' => 'max@example.com', 'name' => 'Max']);
    [$poll] = createPoll();

    $this->actingAs($user)->post("/u/{$poll->public_id}/antwort", ['name' => 'Max', 'email' => 'other@example.com']);

    $participant = Participant::query()->sole();
    expect($participant->email)->toBe('max@example.com')->and($participant->user_id)->toBe($user->id);
    $this->get("/u/{$poll->public_id}")->assertSee('Deine Antwort ändern');
});

it('downgrades maybe to no when maybe is disabled', function () {
    [$poll] = createPoll(['allow_maybe' => false]);

    $this->post("/u/{$poll->public_id}/antwort", ['name' => 'Max', 'email' => 'max@example.com', 'answers' => answersFor($poll, ['maybe'])]);

    expect(Vote::query()->where('answer', 'maybe')->count())->toBe(0);
});

it('rejects invalid answers', function () {
    [$poll] = createPoll();

    $this->post("/u/{$poll->public_id}/antwort", ['name' => 'Max', 'email' => 'max@example.com', 'answers' => [$poll->options->first()->id => 'vielleicht']])
        ->assertSessionHasErrors('answers.'.$poll->options->first()->id);
});

it('ignores answers for options of other polls', function () {
    [$poll] = createPoll();
    [$other] = createPoll();

    $this->post("/u/{$poll->public_id}/antwort", ['name' => 'Max', 'email' => 'max@example.com', 'answers' => [$other->options->first()->id => 'yes']]);

    expect(Vote::query()->where('poll_option_id', $other->options->first()->id)->count())->toBe(0);
});

it('does not accept votes after the poll is finalized', function () {
    [$poll] = createPoll();
    app(FinalizePoll::class)($poll, $poll->options->first());

    $this->post("/u/{$poll->public_id}/antwort", ['name' => 'Max', 'email' => 'max@example.com'])->assertSessionHasErrors('poll');
    expect(Participant::query()->count())->toBe(0);
});

it('notifies the organizer on new votes when enabled', function () {
    [$poll] = createPoll(['notify_on_vote' => true]);

    $this->post("/u/{$poll->public_id}/antwort", ['name' => 'Max', 'email' => 'max@example.com']);

    Mail::assertSent(NewVoteMail::class, fn ($mail) => $mail->hasTo('erika@example.com'));
});

it('does not show participant emails on the public page', function () {
    [$poll] = createPoll();
    $this->post("/u/{$poll->public_id}/antwort", ['name' => 'Max', 'email' => 'geheim@example.com']);

    $this->get("/u/{$poll->public_id}")->assertSee('Max')->assertDontSee('geheim@example.com');
});

it('rejects sql injection payloads as name and email without errors', function (string $payload) {
    [$poll] = createPoll();

    $response = $this->post("/u/{$poll->public_id}/antwort", ['name' => $payload, 'email' => $payload]);

    expect($response->status())->toBeLessThan(500)
        ->and($response->getContent())->not->toContain('SQLSTATE');
})->with(sqlInjectionPayloads());

it('escapes names in the output', function () {
    [$poll] = createPoll();
    $this->post("/u/{$poll->public_id}/antwort", ['name' => '<script>alert(1)</script>', 'email' => 'x@example.com']);

    $this->get("/u/{$poll->public_id}")->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
});

it('rate limits voting', function () {
    [$poll] = createPoll();
    foreach (range(1, 20) as $i) {
        $this->post("/u/{$poll->public_id}/antwort", ['name' => 'X', 'email' => "x{$i}@example.com"]);
    }

    $this->post("/u/{$poll->public_id}/antwort", ['name' => 'X', 'email' => 'y@example.com'])->assertStatus(429);
});
