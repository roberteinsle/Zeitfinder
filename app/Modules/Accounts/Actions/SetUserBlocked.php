<?php

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Validation\ValidationException;

final class SetUserBlocked
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function __invoke(User $actor, User $user, bool $blocked): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages(['user' => __('Du kannst dich nicht selbst sperren.')]);
        }

        $user->forceFill(['blocked_at' => $blocked ? now() : null])->save();
        $this->activity->log($blocked ? 'user.blocked' : 'user.unblocked', $user);
    }
}
