<?php

use App\Modules\Polls\Support\OptionInput;
use App\Support\IcsCalendar;
use App\Support\SafeRedirect;
use App\Support\Tokens;
use Carbon\CarbonImmutable;

it('generates 64 hex tokens and validates the format', function () {
    [$plain, $hash] = Tokens::generate();

    expect($plain)->toMatch('/^[0-9a-f]{64}$/')
        ->and($hash)->toBe(hash('sha256', $plain))
        ->and(Tokens::isWellFormed($plain))->toBeTrue()
        ->and(Tokens::isWellFormed($plain."\n"))->toBeFalse()
        ->and(Tokens::isWellFormed(strtoupper($plain)))->toBeFalse()
        ->and(Tokens::isWellFormed(null))->toBeFalse();
});

it('allows only internal redirects', function (mixed $target, string $expected) {
    expect(SafeRedirect::internalPath($target))->toBe($expected);
})->with([
    ['/konto', '/konto'],
    ['/u/abc?x=1', '/u/abc?x=1'],
    ['https://evil.example.org', '/'],
    ['//evil.example.org', '/'],
    ['/\\evil.example.org', '/'],
    ["/\nfoo", '/'],
    [null, '/'],
    [['/konto'], '/'],
]);

it('converts option rows from Berlin time to UTC and handles midnight crossing', function () {
    $options = OptionInput::toOptions([
        ['date' => '2030-07-01', 'start' => '22:00', 'end' => '01:00'],
        ['date' => '2030-07-01', 'start' => '22:00', 'end' => '01:00'],
        ['date' => '2030-06-30'],
    ]);

    expect($options)->toHaveCount(2)
        ->and($options[0]['all_day'])->toBeTrue()
        ->and($options[1]['starts_at']->format('Y-m-d H:i'))->toBe('2030-07-01 20:00')
        ->and($options[1]['ends_at']->format('Y-m-d H:i'))->toBe('2030-07-01 23:00');
});

it('builds a valid calendar file and escapes text', function () {
    $ics = IcsCalendar::event('uid-1@example.com', 'Essen; mit, Freunden', CarbonImmutable::parse('2030-05-01 16:00', 'UTC'), null, false, "Zeile 1\nZeile 2", 'Ort');

    expect($ics)->toContain("BEGIN:VCALENDAR\r\n")
        ->toContain('SUMMARY:Essen\; mit\, Freunden')
        ->toContain('DESCRIPTION:Zeile 1\nZeile 2')
        ->toContain('DTSTART:20300501T160000Z')
        ->toContain('DTEND:20300501T170000Z');
});

it('uses date values for all-day events', function () {
    $start = CarbonImmutable::parse('2030-05-01 00:00', 'Europe/Berlin')->utc();

    expect(IcsCalendar::event('u', 'X', $start, null, true))
        ->toContain('DTSTART;VALUE=DATE:20300501')
        ->toContain('DTEND;VALUE=DATE:20300502');
});
