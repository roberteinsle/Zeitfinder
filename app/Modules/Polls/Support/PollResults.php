<?php

namespace App\Modules\Polls\Support;

use App\Modules\Polls\Enums\Answer;
use App\Modules\Polls\Models\Poll;
use App\Modules\Polls\Models\PollOption;

/**
 * Aggregates the votes of a poll: counts per option and the best options.
 *
 * Ranking: most "yes", then most "maybe", then fewest "no".
 */
final class PollResults
{
    /** @var array<int, array{yes: int, maybe: int, no: int}> */
    public readonly array $counts;

    /** @var array<int, array<int, Answer>> participant id => option id => answer */
    public readonly array $answers;

    /** @var list<int> */
    public readonly array $bestOptionIds;

    public function __construct(public readonly Poll $poll)
    {
        $poll->loadMissing(['options', 'participants.votes']);

        $counts = [];
        foreach ($poll->options as $option) {
            $counts[$option->id] = ['yes' => 0, 'maybe' => 0, 'no' => 0];
        }

        $answers = [];
        foreach ($poll->participants as $participant) {
            foreach ($participant->votes as $vote) {
                if (! isset($counts[$vote->poll_option_id])) {
                    continue;
                }
                $answers[$participant->id][$vote->poll_option_id] = $vote->answer;
                $counts[$vote->poll_option_id][$vote->answer->value]++;
            }
        }

        $this->counts = $counts;
        $this->answers = $answers;
        $this->bestOptionIds = $this->computeBest($counts);
    }

    public function answer(int $participantId, int $optionId): ?Answer
    {
        return $this->answers[$participantId][$optionId] ?? null;
    }

    public function isBest(PollOption $option): bool
    {
        return in_array($option->id, $this->bestOptionIds, true);
    }

    /**
     * Options sorted best first.
     *
     * @return list<PollOption>
     */
    public function ranked(): array
    {
        $options = $this->poll->options->all();
        usort($options, fn (PollOption $a, PollOption $b) => $this->score($b->id) <=> $this->score($a->id) ?: $a->starts_at <=> $b->starts_at);

        return $options;
    }

    /**
     * Share of participants who can attend (yes + maybe), 0..100.
     */
    public function availability(int $optionId): int
    {
        $total = $this->poll->participants->count();
        if ($total === 0) {
            return 0;
        }
        $c = $this->counts[$optionId] ?? ['yes' => 0, 'maybe' => 0];

        return (int) round(($c['yes'] + $c['maybe']) / $total * 100);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function score(int $optionId): array
    {
        $c = $this->counts[$optionId] ?? ['yes' => 0, 'maybe' => 0, 'no' => 0];

        return [$c['yes'], $c['maybe'], -$c['no']];
    }

    /**
     * @param  array<int, array{yes: int, maybe: int, no: int}>  $counts
     * @return list<int>
     */
    private function computeBest(array $counts): array
    {
        $best = [];
        $bestScore = null;

        foreach (array_keys($counts) as $optionId) {
            $score = $this->score($optionId);
            if ($score[0] === 0 && $score[1] === 0) {
                continue;
            }
            if ($bestScore === null || $score > $bestScore) {
                $best = [$optionId];
                $bestScore = $score;
            } elseif ($score === $bestScore) {
                $best[] = $optionId;
            }
        }

        return $best;
    }
}
