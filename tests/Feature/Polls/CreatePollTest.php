<?php

use App\Models\User;
use App\Modules\Notifications\Mail\PollCreatedMail;
use App\Modules\Polls\Models\Poll;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

function pollPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Teamessen im Dezember',
        'location' => 'Online',
        'description' => 'Wir suchen einen Abend.',
        'organizer_name' => 'Erika Mustermann',
        'organizer_email' => 'Erika@Example.com',
        'allow_maybe' => '1',
        'notify_on_vote' => '0',
        'options' => [
            ['date' => '2030-12-01', 'start' => '18:00', 'end' => '21:00'],
            ['date' => '2030-12-02', 'start' => '', 'end' => ''],
        ],
    ], $overrides);
}

it('shows the create page', function () {
    $this->get('/neu')->assertOk()->assertSee('Neue Umfrage');
});

it('creates a poll anonymously and mails the admin link', function () {
    $response = $this->post('/neu', pollPayload());

    $poll = Poll::query()->sole();
    $response->assertRedirect("/u/{$poll->public_id}/bereit");
    expect($poll->organizer_email)->toBe('erika@example.com')
        ->and($poll->user_id)->toBeNull()
        ->and($poll->options)->toHaveCount(2);

    Mail::assertSent(PollCreatedMail::class, fn (PollCreatedMail $mail) => $mail->hasTo('erika@example.com')
        && preg_match('#/u/'.$poll->public_id.'/verwalten/[0-9a-f]{64}$#', $mail->adminUrl) === 1);

    carryCookies($response, 'zf_manage');
    $this->get("/u/{$poll->public_id}/bereit")->assertOk()->assertSee('Deine Umfrage ist bereit')->assertSee('/verwalten/', false);
});

it('stores times in UTC and all-day options without time', function () {
    $this->post('/neu', pollPayload());
    [$evening, $allDay] = Poll::query()->sole()->options->all();

    // 18:00 in Berlin (CET, winter) is 17:00 UTC.
    expect($evening->starts_at->utc()->format('Y-m-d H:i'))->toBe('2030-12-01 17:00')
        ->and($evening->ends_at->utc()->format('H:i'))->toBe('20:00')
        ->and($evening->all_day)->toBeFalse()
        ->and($allDay->all_day)->toBeTrue()
        ->and($allDay->timeLabel())->toBe('Ganztägig');
});

it('uses the account email for signed-in organizers', function () {
    $user = User::factory()->create(['email' => 'max@example.com', 'name' => 'Max Mustermann']);

    $this->actingAs($user)->post('/neu', pollPayload(['organizer_email' => 'fremd@example.com']));

    $poll = Poll::query()->sole();
    expect($poll->user_id)->toBe($user->id)->and($poll->organizer_email)->toBe('max@example.com');
});

it('validates input', function (array $overrides, string $field) {
    $this->post('/neu', pollPayload($overrides))->assertSessionHasErrors($field);
    expect(Poll::query()->count())->toBe(0);
})->with([
    'no title' => [['title' => ''], 'title'],
    'title too long' => [['title' => str_repeat('a', 121)], 'title'],
    'no options' => [['options' => []], 'options'],
    'bad date' => [['options' => [['date' => '01.12.2030']]], 'options.0.date'],
    'start without end' => [['options' => [['date' => '2030-12-01', 'start' => '18:00', 'end' => '']]], 'options.0.end'],
    'bad email' => [['organizer_email' => 'keine-mail'], 'organizer_email'],
    'too many options' => [['options' => array_fill(0, 61, ['date' => '2030-12-01'])], 'options'],
]);

it('rate limits poll creation', function () {
    foreach (range(1, 20) as $i) {
        $this->post('/neu', pollPayload());
    }

    $this->post('/neu', pollPayload())->assertStatus(429);
});

it('only shows the created page to the organizer', function () {
    [$poll] = createPoll();

    $this->get("/u/{$poll->public_id}/bereit")->assertForbidden();
});
