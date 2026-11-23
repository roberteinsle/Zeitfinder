<?php

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Validation\ValidationException;

final class DeleteUser
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function __invoke(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages(['user' => __('Du kannst dein eigenes Konto hier nicht löschen.')]);
        }

        $this->activity->log('user.deleted', $user, ['email' => $user->email]);
        $user->delete();
    }
}
