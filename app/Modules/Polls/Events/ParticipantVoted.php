<?php

namespace App\Modules\Polls\Events;

use App\Modules\Polls\Models\Participant;
use App\Modules\Polls\Models\Poll;

/**
 * $editToken is set for new participants (plain token for the edit link). Never log it.
 */
final readonly class ParticipantVoted
{
    public function __construct(
        public Poll $poll,
        public Participant $participant,
        public bool $isNew,
        public ?string $editToken = null,
    ) {}
}
