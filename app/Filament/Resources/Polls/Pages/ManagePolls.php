<?php

namespace App\Filament\Resources\Polls\Pages;

use App\Filament\Resources\Polls\PollResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePolls extends ManageRecords
{
    protected static string $resource = PollResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
