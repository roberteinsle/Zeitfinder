<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends AdminOnlyPolicy
{
    public function update(User $user, User $model): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->viewAny($user) && ! $user->is($model);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
