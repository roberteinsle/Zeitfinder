<?php

namespace App\Modules\Polls\Actions;

use App\Modules\Polls\Models\Poll;
use App\Support\ActivityLogger;

final class DeletePoll
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function __invoke(Poll $poll): void
    {
        $this->activity->log('poll.deleted', $poll, ['title' => $poll->title, 'public_id' => $poll->public_id]);
        $poll->delete();
    }
}
