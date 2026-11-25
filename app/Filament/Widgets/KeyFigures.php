<?php

namespace App\Filament\Widgets;

use App\Support\Registries\AdminStat;
use App\Support\Registries\AdminStatRegistry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KeyFigures extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected int|array|null $columns = ['sm' => 2, 'xl' => 3];

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->is_admin && ! $user->isBlocked();
    }

    protected function getStats(): array
    {
        return array_map(function (AdminStat $figure): Stat {
            $value = ($figure->value)();
            $stat = Stat::make($figure->label, number_format($value, 0, ',', '.'));

            if ($figure->description !== null) {
                $stat->description(($figure->description)());
            }

            return $figure->warnAboveZero && $value > 0 ? $stat->color('danger')->descriptionColor('danger') : $stat;
        }, app(AdminStatRegistry::class)->all());
    }
}
