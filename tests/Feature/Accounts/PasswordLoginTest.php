<?php

use App\Models\User;
use App\Modules\Accounts\Actions\EnablePasswordLogin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

function userWithPassword(string $password = 'ein-sehr-langes-passwort'): array
{
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey(32);
    $user = User::factory()->create(['email' => 'erika@example.com']);
    app(EnablePasswordLogin::class)($user, Hash::make($password), $secret);

    return [$user->refresh(), $secret, $google2fa];
}

it('asks for a TOTP code after a correct password and signs in with a valid code', function () {
    [$user, $secret, $g] = userWithPassword();

    $this->post('/anmelden/passwort', ['email' => 'Erika@example.com', 'password' => 'ein-sehr-langes-passwort'])
        ->assertRedirect('/anmelden/zwei-faktor');
    $this->assertGuest();

    $this->post('/anmelden/zwei-faktor', ['code' => $g->getCurrentOtp($secret)])->assertRedirect('/konto');
    $this->assertAuthenticatedAs($user);
    expect(session('auth.method'))->toBe('password_totp');
});

it('rejects a wrong TOTP code', function () {
    userWithPassword();
    $this->post('/anmelden/passwort', ['email' => 'erika@example.com', 'password' => 'ein-sehr-langes-passwort']);

    $this->post('/anmelden/zwei-faktor', ['code' => '000000'])->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('rejects a replayed TOTP code', function () {
    [$user, $secret, $g] = userWithPassword();
    $code = $g->getCurrentOtp($secret);

    $this->post('/anmelden/passwort', ['email' => 'erika@example.com', 'password' => 'ein-sehr-langes-passwort']);
    $this->post('/anmelden/zwei-faktor', ['code' => $code])->assertRedirect('/konto');
    $this->post('/abmelden');

    $this->post('/anmelden/passwort', ['email' => 'erika@example.com', 'password' => 'ein-sehr-langes-passwort']);
    $this->post('/anmelden/zwei-faktor', ['code' => $code])->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('gives the same error for wrong password and unknown address', function () {
    userWithPassword();

    $wrong = $this->post('/anmelden/passwort', ['email' => 'erika@example.com', 'password' => 'falsch-falsch-falsch']);
    $unknown = $this->post('/anmelden/passwort', ['email' => 'niemand@example.com', 'password' => 'falsch-falsch-falsch']);

    expect($wrong->status())->toBe($unknown->status());
    $wrong->assertSessionHasErrors(['password' => 'E-Mail oder Passwort ist falsch.']);
    $unknown->assertSessionHasErrors(['password' => 'E-Mail oder Passwort ist falsch.']);
});

it('does not allow password login without confirmed TOTP', function () {
    $user = User::factory()->create(['email' => 'erika@example.com']);
    $user->forceFill(['password' => Hash::make('ein-sehr-langes-passwort')])->save();

    $this->post('/anmelden/passwort', ['email' => 'erika@example.com', 'password' => 'ein-sehr-langes-passwort'])
        ->assertSessionHasErrors('password');
    $this->get('/anmelden/zwei-faktor')->assertRedirect('/anmelden');
});

it('rejects sql injection payloads in the email field', function (string $payload) {
    userWithPassword();

    $response = $this->post('/anmelden/passwort', ['email' => $payload, 'password' => "' OR '1'='1"]);

    expect($response->status())->toBeLessThan(500)
        ->and($response->getContent())->not->toContain('SQLSTATE');
    $this->assertGuest();
})->with(sqlInjectionPayloads());

it('rate limits password attempts', function () {
    userWithPassword();
    foreach (range(1, 5) as $i) {
        $this->post('/anmelden/passwort', ['email' => 'erika@example.com', 'password' => 'falsch-falsch-falsch']);
    }

    $this->post('/anmelden/passwort', ['email' => 'erika@example.com', 'password' => 'falsch-falsch-falsch'])->assertStatus(429);
});

it('rate limits TOTP attempts', function () {
    userWithPassword();
    $this->post('/anmelden/passwort', ['email' => 'erika@example.com', 'password' => 'ein-sehr-langes-passwort']);
    foreach (range(1, 5) as $i) {
        $this->post('/anmelden/zwei-faktor', ['code' => '000000']);
    }

    $this->post('/anmelden/zwei-faktor', ['code' => '000000'])->assertStatus(429);
});

it('lets a signed-in user enable password login only after confirming TOTP', function () {
    $user = User::factory()->create();
    Cache::flush();

    $this->actingAs($user)->post('/konto/passwort', [
        'password' => 'ein-sehr-langes-passwort-123',
        'password_confirmation' => 'ein-sehr-langes-passwort-123',
    ])->assertRedirect();
    expect($user->refresh()->password)->toBeNull();

    $secret = session('security.pending_password_login.secret');
    $this->get('/konto/sicherheit')->assertOk()->assertSee('<svg', false);

    $this->post('/konto/passwort/bestaetigen', ['code' => '000000'])->assertSessionHasErrors('code');
    expect($user->refresh()->password)->toBeNull();

    $this->post('/konto/passwort/bestaetigen', ['code' => (new Google2FA)->getCurrentOtp($secret)])->assertRedirect('/konto/sicherheit');
    $user->refresh();
    expect($user->hasPasswordLogin())->toBeTrue()
        ->and($user->getRawOriginal('two_factor_secret'))->not->toBe($secret);
});

it('requires a password of at least 12 characters', function () {
    $this->actingAs(User::factory()->create())
        ->post('/konto/passwort', ['password' => 'kurz', 'password_confirmation' => 'kurz'])
        ->assertSessionHasErrors('password');
});

it('can disable password login', function () {
    [$user] = userWithPassword();

    $this->actingAs($user)->delete('/konto/passwort')->assertRedirect('/konto/sicherheit');

    expect($user->refresh()->hasPasswordLogin())->toBeFalse()
        ->and($user->password)->toBeNull();
});
