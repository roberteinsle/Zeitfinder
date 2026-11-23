<?php

use App\Models\User;
use App\Modules\Accounts\Mail\MagicLinkMail;
use App\Modules\Accounts\Models\MagicLink;
use App\Support\Tokens;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => Mail::fake());

function requestLink(string $email): string
{
    test()->post('/anmelden/link', ['email' => $email])->assertRedirect('/anmelden');

    $sent = null;
    Mail::assertSent(MagicLinkMail::class, function (MagicLinkMail $mail) use (&$sent) {
        $sent = $mail;

        return true;
    });

    return (string) preg_replace('#^.*/anmelden/link/#', '', $sent->url);
}

it('sends a link and stores only the hash', function () {
    $token = requestLink('erika@example.com');

    expect($token)->toMatch('/^[0-9a-f]{64}$/');
    $link = MagicLink::query()->sole();
    expect($link->token_hash)->toBe(Tokens::hash($token))
        ->and($link->token_hash)->not->toBe($token)
        ->and($link->expires_at->diffInMinutes(now(), true))->toBeLessThanOrEqual(15);
});

it('normalizes the email address', function () {
    requestLink('  Erika@Example.COM ');

    expect(MagicLink::query()->sole()->email)->toBe('erika@example.com');
});

it('builds the link from APP_URL, never from the Host header', function () {
    config(['app.url' => 'https://zeitfinder.example.com']);
    URL::forceRootUrl('https://zeitfinder.example.com');
    URL::forceScheme('https');

    // Foreign hosts are rejected in production (trusted hosts); either way no link uses them ...
    $response = $this->withHeader('Host', 'evil.example.org')->post('/anmelden/link', ['email' => 'erika@example.com']);
    expect($response->status())->toBeLessThan(500);
    Mail::assertNotSent(MagicLinkMail::class, fn (MagicLinkMail $mail) => str_contains($mail->url, 'evil.example.org'));

    // ... and links always point to APP_URL.
    $this->withHeader('Host', 'zeitfinder.example.com')->post('/anmelden/link', ['email' => 'erika@example.com']);
    Mail::assertSent(MagicLinkMail::class, fn (MagicLinkMail $mail) => str_starts_with($mail->url, 'https://zeitfinder.example.com/anmelden/link/'));
});

it('answers the same for known and unknown addresses', function () {
    User::factory()->create(['email' => 'known@example.com']);

    $known = $this->post('/anmelden/link', ['email' => 'known@example.com']);
    $unknown = $this->post('/anmelden/link', ['email' => 'unknown@example.com']);

    expect($known->status())->toBe($unknown->status())
        ->and($known->headers->get('Location'))->toBe($unknown->headers->get('Location'))
        ->and(session('status'))->toBe(session('status'));
    $this->followRedirects($this->post('/anmelden/link', ['email' => 'other@example.com']))
        ->assertSee('Falls die Adresse gültig ist');
});

it('shows a confirmation page on GET and signs in only on POST', function () {
    $token = requestLink('erika@example.com');

    $this->get("/anmelden/link/{$token}")->assertOk()->assertSee('Jetzt anmelden');
    $this->assertGuest();
    expect(MagicLink::query()->sole()->used_at)->toBeNull();

    $this->post("/anmelden/link/{$token}")->assertRedirect('/konto');
    $this->assertAuthenticated();
    expect(User::query()->sole()->email)->toBe('erika@example.com');
});

it('creates the account on first sign-in and suggests a passkey', function () {
    $token = requestLink('neu@example.com');

    $this->post("/anmelden/link/{$token}")->assertSessionHas('suggest_passkey', true);
    expect(User::query()->where('email', 'neu@example.com')->exists())->toBeTrue();
});

it('can be redeemed only once', function () {
    $token = requestLink('erika@example.com');

    $this->post("/anmelden/link/{$token}")->assertRedirect('/konto');
    $this->post('/abmelden');

    $this->post("/anmelden/link/{$token}")->assertRedirect('/anmelden')->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('rejects expired links', function () {
    $token = requestLink('erika@example.com');
    $this->travel(16)->minutes();

    $this->post("/anmelden/link/{$token}")->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('rejects manipulated tokens', function () {
    $token = requestLink('erika@example.com');
    $manipulated = substr($token, 0, -1).($token[-1] === 'a' ? 'b' : 'a');

    $this->post("/anmelden/link/{$manipulated}")->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('returns 404 for malformed tokens before touching the database', function (string $token) {
    DB::enableQueryLog();

    $this->get('/anmelden/link/'.$token)->assertNotFound();
    $this->post('/anmelden/link/'.$token)->assertNotFound();

    expect(collect(DB::getQueryLog())->pluck('query')->filter(fn ($q) => str_contains($q, 'magic_links')))->toBeEmpty();
})->with([
    'too short' => str_repeat('a', 63),
    'too long' => str_repeat('a', 65),
    'uppercase' => str_repeat('A', 64),
    'non hex' => str_repeat('g', 64),
]);

it('invalidates older links when a new one is requested', function () {
    $first = requestLink('erika@example.com');
    Mail::fake();
    $second = requestLink('erika@example.com');

    $this->post("/anmelden/link/{$first}")->assertSessionHasErrors('email');
    $this->post("/anmelden/link/{$second}")->assertRedirect('/konto');
});

it('regenerates the session on sign-in', function () {
    $token = requestLink('erika@example.com');
    $this->get('/anmelden');
    $before = session()->getId();

    $this->post("/anmelden/link/{$token}");

    expect(session()->getId())->not->toBe($before);
});

it('does not sign in blocked accounts', function () {
    User::factory()->blocked()->create(['email' => 'blocked@example.com']);
    $token = requestLink('blocked@example.com');

    $this->post("/anmelden/link/{$token}")->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('only redirects to internal paths after sign-in', function (string $next, string $expected) {
    $token = requestLink('erika@example.com');

    $this->get('/anmelden?next='.urlencode($next));
    $this->post("/anmelden/link/{$token}")->assertRedirect($expected);
})->with([
    'internal' => ['/neu', '/neu'],
    'absolute' => ['https://evil.example.org/', '/'],
    'protocol relative' => ['//evil.example.org', '/'],
    'backslash' => ['/\\evil.example.org', '/'],
]);

it('rejects sql injection payloads in the email field', function (string $payload) {
    $response = $this->post('/anmelden/link', ['email' => $payload]);

    expect($response->status())->toBeLessThan(500);
    $response->assertSessionHasErrors('email');
    expect($response->getContent())->not->toContain('SQLSTATE');
    expect(MagicLink::query()->count())->toBe(0);
    $this->assertGuest();
})->with(sqlInjectionPayloads());

it('rejects sql injection payloads in the token', function (string $payload) {
    $response = $this->post('/anmelden/link/'.rawurlencode($payload));

    expect($response->status())->toBe(404)
        ->and($response->getContent())->not->toContain('SQLSTATE');
    $this->assertGuest();
})->with(sqlInjectionPayloads());

it('rate limits link requests per address', function () {
    foreach (range(1, 3) as $i) {
        $this->post('/anmelden/link', ['email' => 'erika@example.com'])->assertRedirect();
    }

    $this->post('/anmelden/link', ['email' => 'erika@example.com'])->assertStatus(429);
});

it('rate limits redeeming', function () {
    foreach (range(1, 10) as $i) {
        $this->post('/anmelden/link/'.str_repeat('a', 64));
    }

    $this->post('/anmelden/link/'.str_repeat('a', 64))->assertStatus(429);
});

it('grants admin rights to ADMIN_EMAIL on sign-in', function () {
    config(['zeitfinder.admin_email' => 'Admin@Example.com']);
    $token = requestLink('admin@example.com');

    $this->post("/anmelden/link/{$token}");

    expect(User::query()->sole()->is_admin)->toBeTrue();
});

it('does not grant admin rights to other addresses', function () {
    config(['zeitfinder.admin_email' => 'admin@example.com']);
    $token = requestLink('erika@example.com');

    $this->post("/anmelden/link/{$token}");

    expect(User::query()->sole()->is_admin)->toBeFalse();
});

it('sets referrer policy so tokens do not leak', function () {
    $token = requestLink('erika@example.com');

    $this->get("/anmelden/link/{$token}")->assertHeader('Referrer-Policy', 'no-referrer');
});
