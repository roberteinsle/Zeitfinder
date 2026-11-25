<?php

use App\Modules\Accounts\AccountsServiceProvider;
use App\Modules\Legal\LegalServiceProvider;
use App\Modules\Notifications\NotificationsServiceProvider;
use App\Modules\Polls\PollsServiceProvider;
use App\Modules\Seo\SeoServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    AccountsServiceProvider::class,
    PollsServiceProvider::class,
    NotificationsServiceProvider::class,
    SeoServiceProvider::class,
    LegalServiceProvider::class,
    AdminPanelProvider::class,
];
