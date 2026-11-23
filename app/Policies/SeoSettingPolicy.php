<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Seo\Models\SeoSetting;

class SeoSettingPolicy extends AdminOnlyPolicy
{
    public function update(User $user, SeoSetting $settings): bool
    {
        return $user->is_admin && ! $user->isBlocked();
    }
}
