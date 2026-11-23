<?php

namespace App\Modules\Polls\Actions;

use App\Modules\Polls\Models\Poll;
use App\Modules\Polls\Support\OptionInput;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdatePoll
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * @param  array{title: string, description?: string|null, location?: string|null, allow_maybe?: bool, notify_on_vote?: bool, new_options?: array<int, array{date: string, start?: string|null, end?: string|null}>|null, remove_options?: list<int>|null}  $data
     */
    public function __invoke(Poll $poll, array $data): void
    {
        DB::transaction(function () use ($poll, $data) {
            $poll->fill([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null,
                'allow_maybe' => $data['allow_maybe'] ?? false,
                'notify_on_vote' => $data['notify_on_vote'] ?? false,
            ])->save();

            $remove = array_map('intval', $data['remove_options'] ?? []);
            if ($remove !== []) {
                // The final option cannot be removed.
                $remove = array_values(array_diff($remove, array_filter([$poll->final_option_id])));
                $poll->options()->getQuery()->whereKey($remove)->delete();
            }

            $new = OptionInput::toOptions($data['new_options'] ?? []);
            if ($new !== []) {
                $existing = $poll->options()->get()->map(
                    fn ($o) => $o->starts_at->getTimestamp().'|'.($o->ends_at?->getTimestamp() ?? '').'|'.(int) $o->all_day
                )->all();
                $new = array_filter($new, fn ($o) => ! in_array(
                    $o['starts_at']->getTimestamp().'|'.($o['ends_at']?->getTimestamp() ?? '').'|'.(int) $o['all_day'], $existing, true
                ));
                $poll->options()->createMany($new);
            }

            $max = (int) config('zeitfinder.max_options_per_poll', 60);
            $count = $poll->options()->count();
            if ($count === 0 || $count > $max) {
                throw ValidationException::withMessages([
                    'new_options' => $count === 0
                        ? __('Eine Umfrage braucht mindestens einen Termin.')
                        : __('Höchstens :max Termine pro Umfrage.', ['max' => $max]),
                ]);
            }
        });

        $this->activity->log('poll.updated', $poll, [
            'title' => $poll->title,
            'removed_options' => count($data['remove_options'] ?? []),
            'added_options' => count($data['new_options'] ?? []),
        ]);
    }
}
