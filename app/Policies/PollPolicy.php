<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Polls\Models\Poll;

/**
 * Admin area only. Organizers manage their polls through PollAccess.
 */
class PollPolicy extends AdminOnlyPolicy
{
    public function update(User $user, Poll $poll): bool
    {
        return false;
    }

    public function delete(User $user, Poll $poll): bool
    {
        return $this->viewAny($user);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
