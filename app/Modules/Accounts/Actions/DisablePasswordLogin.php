<?php

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Support\ActivityLogger;

final class DisablePasswordLogin
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function __invoke(User $user): void
    {
        $user->forceFill([
            'password' => null,
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->activity->log('user.password_login_disabled', $user);
    }
}
