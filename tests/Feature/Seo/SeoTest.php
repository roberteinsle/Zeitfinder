<?php

use App\Filament\Pages\SeoSettings;
use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\Accounts\Http\Middleware\EnsurePasskeySession;
use App\Modules\Seo\Actions\IndexNowResult;
use App\Modules\Seo\Actions\SubmitToIndexNow;
use App\Modules\Seo\Models\SeoSetting;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function seo(array $attributes): SeoSetting
{
    $settings = SeoSetting::current();
    $settings->forceFill($attributes)->save();

    return $settings;
}

function actingAsAdmin(): User
{
    $admin = User::factory()->admin()->create();
    test()->actingAs($admin)->withSession([EnsurePasskeySession::SESSION_KEY => time()]);

    return $admin;
}

it('renders meta title, description, canonical and structured data on the home page', function () {
    seo(['home_title' => 'Termine finden', 'home_description' => 'Schnell & einfach abstimmen.', 'organization_name' => 'Muster GmbH']);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<title>Termine finden</title>')
        ->toContain('<meta name="description" content="Schnell &amp; einfach abstimmen.">')
        ->toContain('<meta name="robots" content="index,follow">')
        ->toContain('<link rel="canonical" href="'.route('home').'">')
        ->toContain('<meta property="og:title" content="Termine finden">')
        ->toContain('<script type="application/ld+json" nonce="')
        ->toContain('"@type":"WebApplication"')
        ->toContain('"name":"Muster GmbH"');
});

it('falls back to the default texts when the settings are empty', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('<title>'.e(SeoSetting::DEFAULTS['home_title']).'</title>');
});

it('keeps non-public pages out of the index without canonical or structured data', function () {
    [$poll] = createPoll();

    $html = $this->get(route('polls.show', $poll))->assertOk()->getContent();

    expect($html)
        ->toContain('<meta name="robots" content="noindex">')
        ->toContain('<title>Teamessen · Zeitfinder</title>')
        ->not->toContain('rel="canonical"')
        ->not->toContain('application/ld+json');
});

it('escapes structured data so it cannot close the script tag', function () {
    seo(['organization_name' => '</script><script>alert(1)</script>']);

    expect($this->get('/')->getContent())->not->toContain('</script><script>alert(1)');
});

it('lists only public pages in the sitemap', function () {
    [$poll] = createPoll();

    $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    expect($response->getContent())
        ->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
        ->toContain('<loc>'.route('home').'</loc>')
        ->toContain('<loc>'.route('polls.create').'</loc>')
        ->toContain('<loc>'.route('privacy').'</loc>')
        ->not->toContain($poll->public_id);

    expect(simplexml_load_string($response->getContent()))->not->toBeFalse();
});

it('serves robots.txt with the sitemap and the extra rules', function () {
    seo(['robots_extra' => "User-agent: GPTBot\nDisallow: /"]);

    $body = $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->getContent();

    expect($body)
        ->toStartWith("User-agent: *\n")
        ->toContain("Disallow: /admin\n")
        ->toContain("Disallow: /u/\n")
        ->toContain("User-agent: GPTBot\nDisallow: /")
        ->toContain('Sitemap: '.route('seo.sitemap'));
});

it('serves llms.txt as Markdown with the public pages', function () {
    seo(['llms_intro' => 'Zeitfinder ist eine Terminumfrage & mehr.']);

    $body = $this->get('/llms.txt')->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')->getContent();

    expect($body)
        ->toStartWith('# Zeitfinder')
        ->toContain('Terminumfrage & mehr.')
        ->toContain('('.route('polls.create').')');
});

it('blocks everything when indexing is switched off', function () {
    seo(['indexing_enabled' => false]);

    expect($this->get('/robots.txt')->getContent())->toContain('Disallow: /')->not->toContain('Sitemap:');
    expect($this->get('/sitemap.xml')->getContent())->not->toContain('<url>');
    $this->get('/llms.txt')->assertNotFound();
    expect($this->get('/')->getContent())->toContain('<meta name="robots" content="noindex,nofollow">')->not->toContain('application/ld+json');
});

it('serves the IndexNow key file only for the right key and only when enabled', function () {
    $settings = seo(['indexnow_enabled' => false]);

    $this->get('/'.$settings->indexnow_key.'.txt')->assertNotFound();

    seo(['indexnow_enabled' => true]);
    $this->get('/'.$settings->indexnow_key.'.txt')->assertOk()->assertSeeText($settings->indexnow_key);
    $this->get('/'.str_repeat('a', 32).'.txt')->assertNotFound();
});

it('does not set cookies on crawler files', function () {
    $response = $this->get('/robots.txt');

    expect($response->headers->getCookies())->toBe([]);
});

it('rate limits the crawler files', function () {
    foreach (range(1, 60) as $_) {
        $this->get('/robots.txt')->assertOk();
    }

    $this->get('/robots.txt')->assertTooManyRequests();
});

it('submits all public URLs to IndexNow and limits submissions', function () {
    config(['app.url' => 'https://zeitfinder.example.com']);
    Http::fake([SubmitToIndexNow::ENDPOINT => Http::response('', 202)]);
    $settings = seo(['indexnow_enabled' => true]);

    expect(app(SubmitToIndexNow::class)())->toBe(IndexNowResult::Submitted);

    Http::assertSent(fn (HttpRequest $request) => $request['host'] === 'zeitfinder.example.com'
        && $request['key'] === $settings->indexnow_key
        && in_array(route('home'), $request['urlList'], true)
        && $request['keyLocation'] === route('seo.indexnow-key', ['key' => $settings->indexnow_key]));
    expect($settings->refresh()->indexnow_status)->toBe(202);

    expect(app(SubmitToIndexNow::class)())->toBe(IndexNowResult::Throttled);
    Http::assertSentCount(1);
});

it('does not submit to IndexNow when disabled or without a public https URL', function () {
    Http::fake();
    seo(['indexnow_enabled' => false]);
    config(['app.url' => 'https://zeitfinder.example.com']);
    expect(app(SubmitToIndexNow::class)())->toBe(IndexNowResult::Disabled);

    seo(['indexnow_enabled' => true]);
    config(['app.url' => 'http://localhost:8000']);
    expect(app(SubmitToIndexNow::class)())->toBe(IndexNowResult::NotPublic);

    Http::assertNothingSent();
});

it('lets admins edit the SEO settings and logs the change', function () {
    actingAsAdmin();
    config(['app.url' => 'https://zeitfinder.example.com']);
    Http::fake([SubmitToIndexNow::ENDPOINT => Http::response('', 200)]);

    Livewire::test(SeoSettings::class)
        ->fillForm(['home_title' => '  Neuer Titel  ', 'home_description' => 'Neue Beschreibung', 'indexnow_enabled' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = SeoSetting::current();
    expect($settings->home_title)->toBe('Neuer Titel')
        ->and($settings->home_description)->toBe('Neue Beschreibung')
        ->and(ActivityLog::query()->where('action', 'seo.updated')->exists())->toBeTrue();
    Http::assertSentCount(1);
});

it('validates the length of the SEO texts', function () {
    actingAsAdmin();

    Livewire::test(SeoSettings::class)
        ->fillForm(['home_title' => str_repeat('a', 71), 'home_description' => str_repeat('a', 161)])
        ->call('save')
        ->assertHasFormErrors(['home_title' => 'max', 'home_description' => 'max']);
});

it('does not let normal users open the SEO page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession([EnsurePasskeySession::SESSION_KEY => time()]);

    $this->get('/admin/seo')->assertForbidden();
    expect(SeoSettings::canAccess())->toBeFalse();
});

it('serves versioned, real favicons', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toMatch('/favicon\.svg\?v=[a-f0-9]{8}/')
        ->toContain('apple-touch-icon.png?v=');
    expect(file_get_contents(public_path('favicon.svg')))->toContain('fill="#000000"');
    expect(filesize(public_path('favicon.ico')))->toBeGreaterThan(0);
});
