<?php

namespace App\Modules\Accounts\Support;

use App\Models\User;
use App\Support\EmailAddress;

/**
 * Grants admin rights to the account whose email matches ADMIN_EMAIL.
 * Never revokes rights automatically.
 */
final class AdminBootstrap
{
    public static function sync(User $user): void
    {
        $adminEmail = EmailAddress::normalize(config('zeitfinder.admin_email'));

        if ($adminEmail === '' || $user->is_admin) {
            return;
        }

        if (hash_equals($adminEmail, EmailAddress::normalize($user->email))) {
            $user->forceFill(['is_admin' => true])->save();
        }
    }
}
