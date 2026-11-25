<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\Accounts\Models\MagicLink;
use App\Modules\Polls\Actions\FinalizePoll;
use App\Modules\Polls\Actions\SubmitVote;
use App\Modules\Polls\Models\Participant;
use App\Modules\Polls\Models\Poll;
use App\Modules\Polls\Models\Vote;
use App\Support\Maintenance;
use App\Support\Registries\MaintenanceRegistry;
use Illuminate\Support\Facades\Cache;

/** @param list<string> $dates */
function pollWithDates(array $dates, string $created): Poll
{
    [$poll] = createPoll(['options' => array_map(fn (string $date) => ['date' => $date, 'start' => '18:00', 'end' => '20:00'], $dates)]);
    $poll->forceFill(['created_at' => $created])->save();

    return $poll;
}

beforeEach(fn () => $this->travelTo('2030-07-15 12:00:00'));

it('deletes polls with names, addresses and answers six months after the last date', function () {
    $old = pollWithDates(['2030-01-05', '2030-01-14'], '2029-12-01');
    [$participant] = app(SubmitVote::class)->create($old, 'Max', 'max@example.com', [$old->options->first()->id => 'yes']);
    app(FinalizePoll::class)($old, $old->options->first());

    $kept = pollWithDates(['2030-01-05', '2030-01-16'], '2029-12-01');

    app(Maintenance::class)->run();

    expect(Poll::query()->pluck('id')->all())->toBe([$kept->id])
        ->and(Participant::query()->whereKey($participant->id)->exists())->toBeFalse()
        ->and(Vote::query()->where('participant_id', $participant->id)->exists())->toBeFalse();
});

it('keeps polls with any date in the future or within the period', function () {
    pollWithDates(['2029-01-01', '2031-01-01'], '2028-12-01');
    pollWithDates(['2029-01-01', '2030-02-01'], '2028-12-01');

    app(Maintenance::class)->run();

    expect(Poll::query()->count())->toBe(2);
});

it('keeps new polls even when all dates are long past', function () {
    pollWithDates(['2020-01-01'], '2030-07-01');

    app(Maintenance::class)->run();

    expect(Poll::query()->count())->toBe(1);
});

it('does not cut the period short at the end of a month', function () {
    $this->travelTo('2030-08-31 12:00:00');
    pollWithDates(['2030-03-01'], '2029-12-01');

    app(Maintenance::class)->run();

    expect(Poll::query()->count())->toBe(1);
});

it('deletes expired sign-in links and old log entries, keeps the rest', function () {
    foreach (['a' => '2030-07-15 11:00', 'b' => '2030-07-15 12:10'] as $char => $expires) {
        (new MagicLink)->forceFill(['email' => "{$char}@example.com", 'token_hash' => str_repeat($char, 64), 'expires_at' => $expires])->save();
    }
    foreach (['2030-01-14' => 'old', '2030-01-16' => 'recent'] as $created => $action) {
        (new ActivityLog)->forceFill(['action' => $action, 'created_at' => $created])->save();
    }
    $user = User::factory()->create(['created_at' => '2020-01-01']);

    app(Maintenance::class)->run();

    expect(MagicLink::query()->pluck('email')->all())->toBe(['b@example.com'])
        ->and(ActivityLog::query()->whereIn('action', ['old', 'recent'])->pluck('action')->all())->toBe(['recent'])
        ->and(User::query()->whereKey($user->id)->exists())->toBeTrue();
});

it('logs counts only, no titles or names', function () {
    pollWithDates(['2029-01-01'], '2028-12-01')->forceFill(['title' => 'Geheimes Treffen'])->save();
    ActivityLog::query()->delete();

    app(Maintenance::class)->run();

    $log = ActivityLog::query()->sole();
    expect($log->action)->toBe('maintenance.pruned')
        ->and($log->properties)->toBe(['polls' => 1])
        ->and(json_encode($log))->not->toContain('Geheimes');
});

it('runs once a day through the health check', function () {
    $runs = 0;
    app(MaintenanceRegistry::class)->register('probe', function () use (&$runs) {
        $runs++;

        return 0;
    });

    $this->get('/up')->assertOk();
    $this->get('/up')->assertOk();
    expect($runs)->toBe(1);

    $this->travel(25)->hours();
    $this->get('/up')->assertOk();
    expect($runs)->toBe(2);
});

it('keeps the health check green and the other tasks running when a task fails', function () {
    pollWithDates(['2029-01-01'], '2028-12-01');
    $tasks = app(MaintenanceRegistry::class);
    $tasks->register('magic_links', fn () => throw new RuntimeException('broken'));

    $this->get('/up')->assertOk();
    expect(Poll::query()->count())->toBe(0);
});

it('keeps the health check green when the cache is broken', function () {
    Cache::shouldReceive('add')->andThrow(new RuntimeException('cache down'));

    app(Maintenance::class)->runIfDue();
})->throwsNoExceptions();

it('can be run by hand', function () {
    pollWithDates(['2029-01-01'], '2028-12-01');

    $this->artisan('zeitfinder:maintenance')->expectsOutput('polls: 1')->assertSuccessful();
});
