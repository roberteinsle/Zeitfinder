<?php

use App\Models\User;
use App\Modules\Polls\Actions\CreatePoll;
use App\Modules\Polls\Models\Poll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Run defer() callbacks (mail sending) immediately.
        $this->withoutDefer();
    })
    ->in('Feature', 'Unit');

/**
 * SQL injection payloads required by docs/STANDARDS.md, plain and URL-encoded.
 *
 * @return list<string>
 */
function sqlInjectionPayloads(): array
{
    $payloads = ["' OR '1'='1", "' OR 1=1 --", "admin'--", "'; DROP TABLE users; --", '" OR ""="', "1' UNION SELECT NULL--"];

    return [...$payloads, ...array_map('rawurlencode', $payloads)];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array{0: Poll, 1: string}
 */
function createPoll(array $overrides = [], ?User $user = null): array
{
    return app(CreatePoll::class)(array_merge([
        'title' => 'Teamessen',
        'description' => null,
        'location' => 'Musterstraße 1',
        'organizer_name' => 'Erika Mustermann',
        'organizer_email' => 'erika@example.com',
        'allow_maybe' => true,
        'notify_on_vote' => false,
        'options' => [
            ['date' => '2030-05-01', 'start' => '18:00', 'end' => '20:00'],
            ['date' => '2030-05-02', 'start' => null, 'end' => null],
            ['date' => '2030-05-03', 'start' => '09:30', 'end' => '10:00'],
        ],
    ], $overrides), $user);
}

/**
 * The test client does not keep cookies between requests; carry them over explicitly
 * (like a browser would).
 */
function carryCookies(TestResponse $response, string ...$names): void
{
    foreach ($names as $name) {
        $cookie = $response->getCookie($name);
        if ($cookie !== null) {
            test()->withCookie($name, (string) $cookie->getValue());
        }
    }
}
