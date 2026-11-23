<?php

it('sends security headers', function (string $path) {
    $response = $this->get($path);

    $response->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'no-referrer');
    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'self'")
        ->toContain("frame-ancestors 'none'")
        ->not->toContain('unsafe-eval');
})->with(['/', '/neu', '/anmelden', '/datenschutz']);

it('sends HSTS on https', function () {
    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security');
});

it('loads no external resources on public pages', function () {
    $html = $this->get('/')->getContent();

    expect($html)->not->toMatch('#(src|href)="https?://(?!localhost)#');
});

it('has a rate limit on every public route', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->reject(fn ($route) => $route->uri() === 'up');

    foreach ($routes as $route) {
        $middleware = $route->gatherMiddleware();
        $throttled = collect($middleware)->contains(fn ($m) => is_string($m) && str_contains($m, 'throttle'))
            || in_array('web', $middleware, true);
        expect($throttled)->toBeTrue("Route {$route->uri()} has no rate limit");
    }
});

it('returns 404 pages without debug details', function () {
    config(['app.debug' => false]);

    $this->get('/u/zzzzzzzzzzzz')->assertNotFound()->assertDontSee('Stack trace');
});
