<?php

namespace App\Modules\Polls\Actions;

use App\Modules\Polls\Enums\PollStatus;
use App\Modules\Polls\Models\Poll;
use App\Support\ActivityLogger;

final class ReopenPoll
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function __invoke(Poll $poll): void
    {
        $poll->forceFill([
            'status' => PollStatus::Open,
            'final_option_id' => null,
            'finalized_at' => null,
        ])->save();

        $this->activity->log('poll.reopened', $poll);
    }
}
