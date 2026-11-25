<?php

namespace App\Support\Registries;

use Closure;

/**
 * A key figure on the admin dashboard, contributed by a module. Totals only, no personal data.
 */
final readonly class AdminStat
{
    /**
     * @param  Closure(): int  $value
     * @param  (Closure(): string)|null  $description
     * @param  bool  $warnAboveZero  highlight the figure when it is not zero
     */
    public function __construct(
        public string $key,
        public string $label,
        public Closure $value,
        public ?Closure $description = null,
        public bool $warnAboveZero = false,
        public int $order = 100,
    ) {}
}
