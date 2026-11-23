<?php

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Support\ActivityLogger;

final class UpdateProfile
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function __invoke(User $user, string $name): void
    {
        $user->fill(['name' => $name])->save();
        $this->activity->log('user.profile_updated', $user, ['name' => $name]);
    }
}
