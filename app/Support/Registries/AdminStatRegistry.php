<?php

namespace App\Support\Registries;

/**
 * Key figures on the admin dashboard. Modules register theirs here instead of the
 * dashboard querying their data.
 */
final class AdminStatRegistry
{
    /** @var array<string, AdminStat> */
    private array $stats = [];

    public function register(AdminStat $stat): void
    {
        $this->stats[$stat->key] = $stat;
    }

    /**
     * @return list<AdminStat>
     */
    public function all(): array
    {
        $stats = array_values($this->stats);
        usort($stats, fn (AdminStat $a, AdminStat $b) => $a->order <=> $b->order);

        return $stats;
    }
}
