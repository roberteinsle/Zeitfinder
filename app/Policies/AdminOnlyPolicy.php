<?php

namespace App\Policies;

use App\Models\User;

/**
 * Base for admin-area policies: only admins, never creation through the admin area.
 */
abstract class AdminOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin && ! $user->isBlocked();
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }
}
