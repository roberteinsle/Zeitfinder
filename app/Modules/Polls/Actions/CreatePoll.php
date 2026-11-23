<?php

namespace App\Modules\Polls\Actions;

use App\Models\User;
use App\Modules\Polls\Events\PollCreated;
use App\Modules\Polls\Models\Poll;
use App\Modules\Polls\Support\OptionInput;
use App\Support\ActivityLogger;
use App\Support\Registries\DomainEventRegistry;
use App\Support\Tokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreatePoll
{
    public function __construct(
        private readonly DomainEventRegistry $events,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * @param  array{title: string, description?: string|null, location?: string|null, organizer_name: string, organizer_email: string, allow_maybe?: bool, notify_on_vote?: bool, options: array<int, array{date: string, start?: string|null, end?: string|null}>}  $data
     * @return array{0: Poll, 1: string} the poll and the plain admin token
     */
    public function __invoke(array $data, ?User $user = null): array
    {
        [$adminToken, $adminHash] = Tokens::generate();

        $poll = DB::transaction(function () use ($data, $user, $adminHash) {
            $poll = new Poll([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null,
                'organizer_name' => $data['organizer_name'],
                'organizer_email' => mb_strtolower($data['organizer_email']),
                'allow_maybe' => $data['allow_maybe'] ?? true,
                'notify_on_vote' => $data['notify_on_vote'] ?? false,
            ]);
            $poll->forceFill([
                'public_id' => $this->uniquePublicId(),
                'user_id' => $user?->id,
                'admin_token_hash' => $adminHash,
            ])->save();

            $poll->options()->createMany(OptionInput::toOptions($data['options']));

            return $poll;
        });

        $this->activity->log('poll.created', $poll, ['title' => $poll->title, 'options' => $poll->options()->count()]);
        $this->events->dispatch(new PollCreated($poll, $adminToken));

        return [$poll, $adminToken];
    }

    private function uniquePublicId(): string
    {
        do {
            $id = Str::random(12);
        } while (Poll::query()->where('public_id', $id)->exists());

        return $id;
    }
}
