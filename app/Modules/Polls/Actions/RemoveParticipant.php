<?php

namespace App\Modules\Polls\Actions;

use App\Modules\Polls\Models\Participant;
use App\Modules\Polls\Models\Poll;
use App\Support\ActivityLogger;

final class RemoveParticipant
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function __invoke(Poll $poll, Participant $participant): void
    {
        abort_unless($participant->poll_id === $poll->id, 404);

        $this->activity->log('participant.removed', $participant, ['poll_id' => $poll->id, 'name' => $participant->name]);
        $participant->delete();
    }
}
