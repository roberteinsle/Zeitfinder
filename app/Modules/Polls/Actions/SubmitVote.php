<?php

namespace App\Modules\Polls\Actions;

use App\Models\User;
use App\Modules\Polls\Enums\Answer;
use App\Modules\Polls\Events\DuplicateVoteAttempted;
use App\Modules\Polls\Events\ParticipantVoted;
use App\Modules\Polls\Exceptions\DuplicateParticipantException;
use App\Modules\Polls\Exceptions\PollClosedException;
use App\Modules\Polls\Exceptions\PollFullException;
use App\Modules\Polls\Models\Participant;
use App\Modules\Polls\Models\Poll;
use App\Support\ActivityLogger;
use App\Support\Registries\DomainEventRegistry;
use App\Support\Tokens;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Records or updates the answers of one participant. Each email address can vote
 * once per poll; a second attempt sends the address owner an edit link instead.
 */
final class SubmitVote
{
    public const MAX_PARTICIPANTS = 500;

    public function __construct(
        private readonly DomainEventRegistry $events,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * @param  array<int|string, string>  $answers  option id => answer value
     * @return array{0: Participant, 1: string|null} participant and plain edit token (new participants only)
     *
     * @throws DuplicateParticipantException
     * @throws PollClosedException
     * @throws PollFullException
     */
    public function create(Poll $poll, string $name, string $email, array $answers, ?User $user = null): array
    {
        $this->ensureOpen($poll);
        $email = mb_strtolower(trim($email));

        $existing = $poll->participants()->where('email', $email)->first();
        if ($existing !== null) {
            $this->rejectDuplicate($poll, $existing);
        }

        if ($poll->participants()->count() >= self::MAX_PARTICIPANTS) {
            throw new PollFullException;
        }

        [$token, $hash] = Tokens::generate();

        try {
            $participant = DB::transaction(function () use ($poll, $name, $email, $answers, $user, $hash) {
                $participant = new Participant(['name' => $name]);
                $participant->forceFill([
                    'poll_id' => $poll->id,
                    'user_id' => $user?->id,
                    'email' => $email,
                    'edit_token_hash' => $hash,
                ])->save();

                $this->storeAnswers($poll, $participant, $answers);

                return $participant;
            });
        } catch (UniqueConstraintViolationException) {
            // Two submissions with the same address at the same time.
            $existing = $poll->participants()->where('email', $email)->firstOrFail();
            $this->rejectDuplicate($poll, $existing);
        }

        $this->activity->log('participant.created', $participant, ['poll_id' => $poll->id, 'name' => $name]);
        $this->events->dispatch(new ParticipantVoted($poll, $participant, true, $token));

        return [$participant, $token];
    }

    /**
     * @param  array<int|string, string>  $answers
     *
     * @throws PollClosedException
     */
    public function update(Poll $poll, Participant $participant, string $name, array $answers): void
    {
        $this->ensureOpen($poll);
        abort_unless($participant->poll_id === $poll->id, 404);

        DB::transaction(function () use ($poll, $participant, $name, $answers) {
            $participant->fill(['name' => $name])->save();
            $this->storeAnswers($poll, $participant, $answers);
        });

        $this->activity->log('participant.updated', $participant, ['poll_id' => $poll->id, 'name' => $name]);
        $this->events->dispatch(new ParticipantVoted($poll, $participant, false));
    }

    /**
     * @param  array<int|string, string>  $answers
     */
    private function storeAnswers(Poll $poll, Participant $participant, array $answers): void
    {
        $now = now();
        $rows = [];

        foreach ($poll->options()->pluck('id') as $optionId) {
            $answer = Answer::tryFrom((string) ($answers[$optionId] ?? '')) ?? Answer::No;
            if ($answer === Answer::Maybe && ! $poll->allow_maybe) {
                $answer = Answer::No;
            }
            $rows[] = [
                'participant_id' => $participant->id,
                'poll_option_id' => $optionId,
                'answer' => $answer->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('votes')->upsert($rows, ['participant_id', 'poll_option_id'], ['answer', 'updated_at']);
    }

    /**
     * @throws DuplicateParticipantException
     */
    private function rejectDuplicate(Poll $poll, Participant $existing): never
    {
        // Rotate the edit token and mail it to the owner of the address.
        [$token, $hash] = Tokens::generate();
        $existing->forceFill(['edit_token_hash' => $hash])->save();

        $this->activity->log('participant.duplicate_attempt', $existing, ['poll_id' => $poll->id]);
        $this->events->dispatch(new DuplicateVoteAttempted($poll, $existing, $token));

        throw new DuplicateParticipantException;
    }

    private function ensureOpen(Poll $poll): void
    {
        if (! $poll->isOpen()) {
            throw new PollClosedException;
        }
    }
}
