<?php

namespace App\Modules\Polls\Events;

use App\Modules\Polls\Models\Participant;
use App\Modules\Polls\Models\Poll;

/**
 * Someone tried to vote again with an address that already voted. The owner of the
 * address receives a fresh edit link. Never log $editToken.
 */
final readonly class DuplicateVoteAttempted
{
    public function __construct(public Poll $poll, public Participant $participant, public string $editToken) {}
}
