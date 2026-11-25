<?php

use App\Models\User;
use App\Modules\Polls\Actions\FinalizePoll;
use App\Modules\Polls\Http\Middleware\KeepOutCrawlers;
use App\Support\Crawlers;

const BROWSERS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Ddg/18.5',
    'Mozilla/5.0 (X11; Linux x86_64; rv:140.0) Gecko/20100101 Firefox/140.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 YaBrowser/25.6.0.0 Safari/537.36',
    'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    'WhatsApp/2.23.20.0',
    'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)',
];

it('refuses known search engine and AI crawlers on poll pages', function (string $userAgent) {
    [$poll] = createPoll(['title' => 'Geheimes Treffen']);

    $this->get("/u/{$poll->public_id}", ['User-Agent' => $userAgent])
        ->assertForbidden()
        ->assertDontSee('Geheimes Treffen')
        ->assertHeader('X-Robots-Tag', Crawlers::PRIVATE_PAGE)
        ->assertHeader('X-Frame-Options', 'DENY');
})->with([
    'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
    'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)',
    'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot',
    'Mozilla/5.0 (compatible; ClaudeBot/1.0; +claudebot@anthropic.com)',
    'Mozilla/5.0 (compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)',
    'CCBot/2.0 (https://commoncrawl.org/faq/)',
    'mozilla/5.0 (compatible; GPTBOT/9.9)',
]);

it('refuses crawlers on every route below /u/', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())->filter(fn ($route) => str_starts_with($route->uri(), 'u/'));

    expect($routes)->not->toBeEmpty();
    foreach ($routes as $route) {
        expect($route->gatherMiddleware()[0])->toBe(KeepOutCrawlers::class);
    }
});

it('lets people and messenger previews in, marked as not indexable', function (string $userAgent) {
    [$poll] = createPoll();

    $this->get("/u/{$poll->public_id}", ['User-Agent' => $userAgent])
        ->assertOk()
        ->assertHeader('X-Robots-Tag', Crawlers::PRIVATE_PAGE);
})->with(BROWSERS);

it('marks the calendar file and unknown polls as not indexable', function () {
    [$poll] = createPoll();
    app(FinalizePoll::class)($poll, $poll->options->first());

    $this->get("/u/{$poll->public_id}/termin.ics")->assertOk()->assertHeader('X-Robots-Tag', Crawlers::PRIVATE_PAGE);
    $this->get('/u/aaaaaaaaaaaa')->assertNotFound()->assertHeader('X-Robots-Tag', Crawlers::PRIVATE_PAGE);
});

it('still lets search engines crawl the home page', function () {
    $this->get('/', ['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])->assertOk()->assertHeaderMissing('X-Robots-Tag');
});

it('names every crawler in robots.txt with the private paths', function () {
    $body = $this->get('/robots.txt')->getContent();
    $group = substr($body, (int) strpos($body, 'User-agent: Googlebot'));

    foreach ([...Crawlers::TOKENS, ...Crawlers::ROBOTS_ONLY] as $token) {
        expect($group)->toContain("User-agent: {$token}\n");
    }
    expect($group)->toContain("Disallow: /u/\n")->not->toContain("Disallow: /\n");
});

it('tells participants that nobody else sees their address', function () {
    [$poll] = createPoll();

    $this->get("/u/{$poll->public_id}")->assertSee('weder der Organisator noch andere Teilnehmende sehen deine Adresse')->assertDontSee('Nur für die Organisation sichtbar');
    $this->actingAs(User::factory()->create())->get("/u/{$poll->public_id}")->assertSee('weder der Organisator noch andere Teilnehmende sehen deine Adresse');
});

it('does not suggest a name that is part of the email address', function () {
    [$poll] = createPoll();

    $auto = User::factory()->create(['name' => 'max.geheim', 'email' => 'max.geheim@example.com']);
    $this->actingAs($auto)->get("/u/{$poll->public_id}")->assertDontSee('value="max.geheim"', false);

    $chosen = User::factory()->create(['name' => 'Max Mustermann', 'email' => 'max@example.com']);
    $this->actingAs($chosen)->get("/u/{$poll->public_id}")->assertSee('value="Max Mustermann"', false);
});
