<?php

namespace App\Support\Registries;

use Closure;

/**
 * Daily clean-up tasks. Each module deletes its own outdated data and registers the task here.
 */
final class MaintenanceRegistry
{
    /** @var array<string, Closure(): int> */
    private array $tasks = [];

    /**
     * @param  Closure(): int  $task  returns the number of deleted records
     */
    public function register(string $key, Closure $task): void
    {
        $this->tasks[$key] = $task;
    }

    /**
     * @return array<string, Closure(): int>
     */
    public function all(): array
    {
        return $this->tasks;
    }
}
