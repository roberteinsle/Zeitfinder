<?php

namespace App\Modules\Polls\Actions;

use App\Modules\Polls\Models\Poll;
use Illuminate\Database\Eloquent\Builder;

/**
 * Deletes polls with all answers once every proposed date is older than the retention period.
 * The poll itself must be that old too, so a new poll with dates in the past is kept.
 */
final class PruneOldPolls
{
    public function __invoke(): int
    {
        $cutoff = now()->subMonthsNoOverflow((int) config('zeitfinder.retention_months'));

        $count = 0;
        Poll::query()
            ->where('created_at', '<', $cutoff)
            ->whereDoesntHave('options', fn (Builder $options) => $options->where(
                fn (Builder $q) => $q->where('starts_at', '>=', $cutoff)->orWhere('ends_at', '>=', $cutoff),
            ))
            ->lazyById(100)
            ->each(function (Poll $poll) use (&$count) {
                $poll->delete();
                $count++;
            });

        return $count;
    }
}
