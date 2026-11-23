<?php

use App\Modules\Accounts\AccountsServiceProvider;
use App\Modules\Notifications\NotificationsServiceProvider;
use App\Modules\Polls\PollsServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    AccountsServiceProvider::class,
    PollsServiceProvider::class,
    NotificationsServiceProvider::class,
    AdminPanelProvider::class,
];
