<?php

namespace App\Console\Commands;

use App\Support\Maintenance;
use Illuminate\Console\Command;

class MaintenanceCommand extends Command
{
    protected $signature = 'zeitfinder:maintenance';

    protected $description = 'Delete outdated polls, sign-in links and activity log entries now';

    public function handle(Maintenance $maintenance): int
    {
        foreach ($maintenance->run() as $task => $count) {
            $this->line("{$task}: {$count}");
        }

        return self::SUCCESS;
    }
}
