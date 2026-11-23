<?php

namespace App\Modules\Polls\Events;

use App\Modules\Polls\Models\Poll;

/**
 * Carries the plain admin token so the organizer can be mailed the admin link.
 * Never log this object.
 */
final readonly class PollCreated
{
    public function __construct(public Poll $poll, public string $adminToken) {}
}
