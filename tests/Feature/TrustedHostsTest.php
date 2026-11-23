<?php

use Illuminate\Http\Middleware\TrustHosts;

// The middleware is skipped while running tests, so check the configured patterns directly.
function hostIsTrusted(string $host): bool
{
    foreach (app(TrustHosts::class)->hosts() as $pattern) {
        if ($pattern !== null && preg_match('{'.$pattern.'}i', $host) === 1) {
            return true;
        }
    }

    return false;
}

it('trusts the APP_URL host and localhost for the container health check', function () {
    config(['app.url' => 'https://zeitfinder.example.com']);

    expect(hostIsTrusted('zeitfinder.example.com'))->toBeTrue()
        ->and(hostIsTrusted('localhost'))->toBeTrue();
});

it('rejects foreign hosts', function (string $host) {
    config(['app.url' => 'https://zeitfinder.example.com']);

    expect(hostIsTrusted($host))->toBeFalse();
})->with(['evil.example.org', 'localhost.evil.example.org', 'zeitfinder.example.com.evil.example.org']);

it('answers the health check', function () {
    $this->get('/up')->assertOk();
});
