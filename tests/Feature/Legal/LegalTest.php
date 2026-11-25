<?php

use App\Filament\Pages\LegalSettings;
use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\Accounts\Http\Middleware\EnsurePasskeySession;
use App\Modules\Legal\Models\LegalSetting;
use Livewire\Livewire;

/** @param array<string, string|null> $attributes */
function legal(array $attributes = []): void
{
    LegalSetting::current()->forceFill([
        'operator_name' => 'Erika Mustermann',
        'operator_address' => "Musterstraße 1\n12345 Musterstadt",
        'contact_email' => 'kontakt@example.com',
        ...$attributes,
    ])->save();
}

it('hides the imprint until name, address and email are complete', function (string $missing) {
    legal([$missing => null]);

    $this->get('/impressum')->assertNotFound();
    $this->get('/')->assertDontSee(route('imprint'));
    $this->get('/datenschutz')->assertOk()->assertDontSee('Verantwortlich</h2>', false);
})->with(['operator_name', 'operator_address', 'contact_email']);

it('shows the imprint and links it on public pages', function () {
    legal(['contact_phone' => '+49 30 000000']);
    [$poll] = createPoll();

    $this->get('/impressum')->assertOk()
        ->assertSee('Erika Mustermann')->assertSee('Musterstraße 1')->assertSee('kontakt@example.com')->assertSee('+49 30 000000')
        ->assertSee('<meta name="robots" content="noindex">', false);
    foreach (['/', '/neu', '/anmelden', '/datenschutz', "/u/{$poll->public_id}"] as $path) {
        $this->get($path)->assertSee('href="'.route('imprint').'"', false)->assertSee('href="'.route('privacy').'"', false);
    }
    expect($this->get('/sitemap.xml')->getContent())->toContain(route('privacy'))->not->toContain('impressum');
});

it('does not hide the footer on phones', function () {
    preg_match('#<footer class="([^"]*)"#', $this->get('/')->getContent(), $match);

    expect(explode(' ', $match[1]))->not->toContain('hidden');
});

it('states operator, providers, retention and authority in the privacy notice', function () {
    legal(['hosting_provider' => 'Muster Hosting GmbH', 'processors' => 'Muster Mail AG', 'supervisory_authority' => 'Musterbehörde', 'privacy_extra' => 'Zusatz']);
    config(['zeitfinder.retention_months' => 7]);

    $this->get('/datenschutz')->assertOk()
        ->assertSeeInOrder(['Verantwortlich', 'Erika Mustermann', 'Rechtsgrundlagen', 'Muster Hosting GmbH', 'Muster Mail AG', '7 Monate nach dem letzten', 'Widerspruchsrecht', 'Musterbehörde', 'Zusatz']);
});

it('escapes the texts', function () {
    legal(['operator_name' => '<script>alert(1)</script>', 'imprint_extra' => '<img src=x>']);

    $this->get('/impressum')->assertDontSee('<script>alert(1)', false)->assertDontSee('<img src=x>', false);
});

it('lets admins edit the details and logs field names only', function () {
    $this->actingAs(User::factory()->admin()->create())->withSession([EnsurePasskeySession::SESSION_KEY => time()]);

    Livewire::test(LegalSettings::class)
        ->fillForm(['operator_name' => ' Erika Mustermann ', 'operator_address' => 'Musterstraße 1', 'contact_email' => 'kontakt@example.com'])
        ->call('save')
        ->assertHasNoFormErrors();

    $log = ActivityLog::query()->where('action', 'legal.updated')->sole();
    expect(LegalSetting::current()->operator_name)->toBe('Erika Mustermann')
        ->and(json_encode($log->properties))->toContain('operator_name')->not->toContain('Erika');
});

it('validates the fields', function () {
    $this->actingAs(User::factory()->admin()->create())->withSession([EnsurePasskeySession::SESSION_KEY => time()]);

    Livewire::test(LegalSettings::class)
        ->fillForm(['operator_name' => str_repeat('a', 121), 'contact_email' => 'keine-adresse'])
        ->call('save')
        ->assertHasFormErrors(['operator_name' => 'max', 'contact_email' => 'email']);
});

it('does not let normal users or guests open the page', function () {
    $this->get('/admin/rechtliches')->assertRedirect();

    $this->actingAs(User::factory()->create())->withSession([EnsurePasskeySession::SESSION_KEY => time()]);
    $this->get('/admin/rechtliches')->assertForbidden();
    expect(LegalSettings::canAccess())->toBeFalse();
});
