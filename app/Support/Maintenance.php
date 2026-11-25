<?php

namespace App\Support;

use App\Support\Registries\MaintenanceRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs the registered clean-up tasks about once a day. There is no scheduler container; the
 * container health check (/up, every 30 seconds) triggers it, independent of visitors.
 */
final class Maintenance
{
    private const LOCK = 'maintenance:ran';

    public function __construct(
        private readonly MaintenanceRegistry $registry,
        private readonly ActivityLogger $activity,
    ) {}

    /** Never throws: a failing clean-up must not turn the health check red. */
    public function runIfDue(): void
    {
        try {
            if (Cache::add(self::LOCK, true, now()->addDay())) {
                defer(fn () => $this->run())->always();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @return array<string, int> deleted records per task
     */
    public function run(): array
    {
        $deleted = [];
        foreach ($this->registry->all() as $key => $task) {
            try {
                $deleted[$key] = $task();
            } catch (Throwable $e) {
                report($e);
                // Try again in an hour instead of tomorrow.
                Cache::put(self::LOCK, true, now()->addHour());
            }
        }

        // Counts only, no names or titles: the log must not keep what was just deleted.
        $done = array_filter($deleted);
        Log::info('Maintenance finished.', $deleted);
        if ($done !== []) {
            $this->activity->log('maintenance.pruned', null, $done);
        }

        return $deleted;
    }
}
