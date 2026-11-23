<?php

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Validation\ValidationException;

final class SetUserAdmin
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function __invoke(User $actor, User $user, bool $admin): void
    {
        if ($actor->is($user) && ! $admin) {
            throw ValidationException::withMessages(['user' => __('Du kannst dir die Admin-Rechte nicht selbst entziehen.')]);
        }

        $user->forceFill(['is_admin' => $admin])->save();
        $this->activity->log($admin ? 'user.admin_granted' : 'user.admin_revoked', $user);
    }
}
