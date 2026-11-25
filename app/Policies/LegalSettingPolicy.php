<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Legal\Models\LegalSetting;

class LegalSettingPolicy extends AdminOnlyPolicy
{
    public function update(User $user, LegalSetting $settings): bool
    {
        return $user->is_admin && ! $user->isBlocked();
    }
}
