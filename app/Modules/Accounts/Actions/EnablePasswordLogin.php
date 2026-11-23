<?php

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Support\ActivityLogger;

/**
 * Saves password and TOTP secret together, after the user confirmed a TOTP code.
 * A password without confirmed TOTP never exists.
 */
final class EnablePasswordLogin
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function __invoke(User $user, string $passwordHash, string $secret): void
    {
        $user->forceFill([
            'password' => $passwordHash,
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->activity->log('user.password_login_enabled', $user);
    }
}
