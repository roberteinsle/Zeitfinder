<?php

use App\Models\User;
use App\Modules\Accounts\Http\Middleware\EnsurePasskeySession;
use App\Modules\Accounts\Mail\MagicLinkMail;
use Laravel\Passkeys\Events\PasskeyVerified;
use Laravel\Passkeys\Passkey;

function passkeyFor(User $user): Passkey
{
    return $user->passkeys()->forceCreate([
        'user_id' => $user->id,
        'name' => 'Testgerät',
        'credential_id' => bin2hex(random_bytes(16)),
        'credential' => ['test' => true],
    ]);
}

it('redirects guests from the admin area to the login page', function () {
    $this->get('/admin')->assertRedirect('/anmelden');
});

it('forbids the admin area for normal users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession([EnsurePasskeySession::SESSION_KEY => time()])
        ->get('/admin')->assertForbidden();
});

it('requires a passkey-verified session for admins', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin')->assertRedirect('/konto/passkey-bestaetigen?next=%2Fadmin');
    $this->get('/konto/passkey-bestaetigen?next=/admin')->assertOk()->assertSee('Passkey einrichten');
});

it('lets admins in after a passkey verification', function () {
    $admin = User::factory()->admin()->create();
    $passkey = passkeyFor($admin);
    $this->actingAs($admin)->get('/konto');

    event(new PasskeyVerified($admin, $passkey));

    expect(session(EnsurePasskeySession::SESSION_KEY))->not->toBeNull();
    $this->get('/admin')->assertOk();
    $this->get('/admin/benutzer')->assertOk()->assertSee($admin->email);
    $this->get('/admin/umfragen')->assertOk();
    $this->get('/admin/protokoll')->assertOk();
});

it('drops the passkey flag after a magic link sign-in', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $this->withSession([EnsurePasskeySession::SESSION_KEY => time()]);

    $this->post('/anmelden/link', ['email' => 'admin@example.com']);
    $url = null;
    Mail::assertSent(MagicLinkMail::class, function ($m) use (&$url) {
        $url = $m->url;

        return true;
    });
    $this->post((string) parse_url($url, PHP_URL_PATH));

    $this->assertAuthenticatedAs($admin);
    expect(session(EnsurePasskeySession::SESSION_KEY))->toBeNull();
    $this->get('/admin')->assertRedirect();
});

it('blocks blocked admins even with a passkey session', function () {
    $admin = User::factory()->admin()->blocked()->create();

    $this->actingAs($admin)->withSession([EnsurePasskeySession::SESSION_KEY => time()])
        ->get('/admin')->assertForbidden();
    // Any normal page ends the session of a blocked account.
    $this->get('/konto')->assertRedirect('/anmelden');
    $this->assertGuest();
});

it('prevents admins with passkeys from adding passkeys without passkey verification', function () {
    $admin = User::factory()->admin()->create();
    passkeyFor($admin);

    $this->actingAs($admin)->getJson('/user/passkeys/options')->assertForbidden();
    $this->withSession([EnsurePasskeySession::SESSION_KEY => time()])->getJson('/user/passkeys/options')->assertOk();
});

it('lets normal users fetch passkey registration options', function () {
    $this->actingAs(User::factory()->create())->getJson('/user/passkeys/options')
        ->assertOk()->assertJsonStructure(['options' => ['challenge', 'rp', 'user']]);
});

it('offers passkey login options to guests', function () {
    $this->getJson('/passkeys/login/options')->assertOk()->assertJsonStructure(['options' => ['challenge']]);
});

it('rejects garbage passkey credentials without a server error', function () {
    $this->getJson('/passkeys/login/options');

    $response = $this->postJson('/passkeys/login', ['credential' => ['id' => "' OR '1'='1", 'rawId' => "' OR 1=1 --", 'type' => 'public-key', 'response' => ['x' => 'y']]]);

    expect($response->status())->toBe(422)
        ->and($response->getContent())->not->toContain('SQLSTATE');
    $this->assertGuest();
});

it('lets users remove their own passkeys only', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $mine = passkeyFor($user);
    $foreign = passkeyFor($other);

    $this->actingAs($user)->delete("/user/passkeys/{$foreign->id}")->assertForbidden();
    $this->delete("/user/passkeys/{$mine->id}")->assertRedirect();

    expect(Passkey::query()->whereKey($mine->id)->exists())->toBeFalse()
        ->and(Passkey::query()->whereKey($foreign->id)->exists())->toBeTrue();
});

it('removes all passkeys with the emergency command', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    passkeyFor($admin);

    $this->artisan('zeitfinder:reset-passkeys', ['email' => 'admin@example.com'])->assertSuccessful();

    expect($admin->passkeys()->count())->toBe(0);
});
