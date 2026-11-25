<?php

use App\Filament\LocalAvatarProvider;
use App\Filament\Widgets\KeyFigures;
use App\Models\User;
use App\Modules\Accounts\Http\Middleware\EnsurePasskeySession;
use App\Modules\Polls\Actions\FinalizePoll;
use App\Modules\Polls\Actions\SubmitVote;
use App\Support\ActivityLogger;
use App\Support\Registries\AdminStatRegistry;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Erika Mustermann']);
    $this->actingAs($this->admin)->withSession([EnsurePasskeySession::SESSION_KEY => time()]);
});

function figure(string $key): int
{
    return (collect(app(AdminStatRegistry::class)->all())->firstOrFail('key', $key)->value)();
}

it('shows key figures instead of the welcome box', function () {
    $this->get('/admin')->assertOk()
        ->assertSeeInOrder(['Offene Umfragen', 'Neue Umfragen', 'Teilnahmen', 'Festgelegte Termine', 'Benutzer', 'Fehlgeschlagene Mails'])
        ->assertDontSee('Willkommen');
});

it('counts open, new and finalized polls, votes, users and failed mails', function () {
    [$open] = createPoll();
    [$done] = createPoll();
    app(FinalizePoll::class)($done, $done->options->first());
    [$old] = createPoll();
    $old->forceFill(['created_at' => now()->subDays(31)])->save();
    app(SubmitVote::class)->create($open, 'Max', 'max@example.com', []);
    [$oldVote] = app(SubmitVote::class)->create($open, 'Lisa', 'lisa@example.com', []);
    $oldVote->forceFill(['created_at' => now()->subDays(31)])->save();
    app(ActivityLogger::class)->log('mail.failed', null, ['failed' => 2, 'total' => 3]);
    app(ActivityLogger::class)->log('mail.failed', null, ['failed' => 1, 'total' => 1]);

    expect(figure('polls.open'))->toBe(2)
        ->and(figure('polls.new'))->toBe(2)
        ->and(figure('polls.votes'))->toBe(1)
        ->and(figure('polls.finalized'))->toBe(1)
        ->and(figure('accounts.users'))->toBe(1)
        ->and(figure('notifications.failed'))->toBe(3);
    Livewire::test(KeyFigures::class)->assertSee('von 3 insgesamt');
});

it('links back to the site right below the dashboard', function () {
    $this->get('/admin')->assertSeeInOrder(['fi-sidebar-item', 'Dashboard', 'href="'.route('home').'"', 'Zurück zu Zeitfinder', 'Umfragen'], false);
});

it('uses a local avatar and loads nothing from other hosts', function () {
    $html = $this->get('/admin')->getContent();

    expect($html)->toContain('data:image/svg+xml;base64,')->not->toContain('ui-avatars.com')
        ->not->toMatch('#<(?:img|script|link)[^>]+(?:src|href)="https?://(?!localhost)#');
});

it('builds initials safely', function (string $name, string $expected) {
    $svg = base64_decode(substr((new LocalAvatarProvider)->get(User::factory()->make(['name' => $name])), strlen('data:image/svg+xml;base64,')));

    expect(simplexml_load_string($svg))->not->toBeFalse()
        ->and($svg)->toContain(">{$expected}</text>")
        ->not->toContain('<script');
})->with([['Erika Mustermann', 'EM'], ['max', 'M'], ['Anna von der Öde', 'AÖ'], ['<script>', 'S'], ['&&', '?']]);

it('hides the figures from normal users', function () {
    $this->actingAs(User::factory()->create());

    expect(KeyFigures::canView())->toBeFalse();
    $this->get('/admin')->assertForbidden();
});
