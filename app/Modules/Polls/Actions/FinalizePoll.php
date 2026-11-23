<?php

namespace App\Modules\Polls\Actions;

use App\Modules\Polls\Enums\PollStatus;
use App\Modules\Polls\Events\PollFinalized;
use App\Modules\Polls\Models\Poll;
use App\Modules\Polls\Models\PollOption;
use App\Support\ActivityLogger;
use App\Support\Registries\DomainEventRegistry;

/**
 * Fixes the final date. Listeners (notifications module) inform everybody.
 */
final class FinalizePoll
{
    public function __construct(
        private readonly DomainEventRegistry $events,
        private readonly ActivityLogger $activity,
    ) {}

    public function __invoke(Poll $poll, PollOption $option): void
    {
        abort_unless($option->poll_id === $poll->id, 404);

        $poll->forceFill([
            'status' => PollStatus::Finalized,
            'final_option_id' => $option->id,
            'finalized_at' => now(),
        ])->save();

        $this->activity->log('poll.finalized', $poll, ['option_id' => $option->id]);
        $this->events->dispatch(new PollFinalized($poll, $option));
    }
}
