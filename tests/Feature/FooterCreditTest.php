<?php

function footerText(string $html): string
{
    preg_match('#<footer.*?</footer>#s', $html, $match);

    return trim((string) preg_replace('/\s+/u', ' ', strip_tags($match[0] ?? '')));
}

it('shows the credit with a link in the footer', function () {
    config(['zeitfinder.credit' => ['name' => 'Erika Mustermann', 'url' => 'https://example.com/erika', 'place' => 'Musterstadt']]);

    $html = $this->get('/')->assertOk()->getContent();

    expect(footerText($html))->toContain('Made with ♥️ in Musterstadt by Erika Mustermann')
        ->and($html)->toMatch('#<a href="https://example\.com/erika"[^>]*>Erika Mustermann</a>#');
});

it('hides the credit without a name', function () {
    config(['zeitfinder.credit' => ['name' => null, 'url' => 'https://example.com/erika', 'place' => 'Musterstadt']]);

    expect(footerText($this->get('/')->getContent()))->not->toContain('Made with');
});

it('escapes the name and does not link other URL schemes', function () {
    config(['zeitfinder.credit' => ['name' => '<b>Erika</b>', 'url' => 'javascript:alert(1)', 'place' => null]]);

    expect($this->get('/')->getContent())->toContain('Made with ♥️ by')
        ->toContain('&lt;b&gt;Erika&lt;/b&gt;')
        ->not->toContain('javascript:alert');
});
