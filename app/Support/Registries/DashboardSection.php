<?php

namespace App\Support\Registries;

use App\Models\User;
use Closure;

/**
 * A section on the personal account page, contributed by a module.
 */
final readonly class DashboardSection
{
    /**
     * @param  Closure(User): array<string, mixed>  $data  view data for the section
     */
    public function __construct(
        public string $key,
        public string $view,
        public Closure $data,
        public int $order = 100,
    ) {}
}
