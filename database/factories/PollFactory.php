<?php

namespace Database\Factories;

use App\Modules\Polls\Models\Poll;
use App\Support\Tokens;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Poll>
 */
class PollFactory extends Factory
{
    protected $model = Poll::class;

    public function definition(): array
    {
        return [
            'title' => 'Teamessen',
            'organizer_name' => 'Erika Mustermann',
            'organizer_email' => 'erika@example.com',
            'allow_maybe' => true,
            'notify_on_vote' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Poll $poll) {
            $poll->forceFill([
                'public_id' => $poll->public_id ?? Str::random(12),
                'admin_token_hash' => $poll->admin_token_hash ?? Tokens::generate()[1],
            ]);
        });
    }
}
