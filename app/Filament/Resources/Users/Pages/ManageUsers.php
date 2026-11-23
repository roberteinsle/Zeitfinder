<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    // Accounts are created by signing in, not in the admin area.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
