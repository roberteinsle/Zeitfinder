<?php

namespace App\Support\Registries;

use Closure;

/**
 * A partial rendered into <head> of the public layout, contributed by a module.
 * The layout only renders its own fallback <title> and robots tag when nothing is registered.
 */
final readonly class HeadContribution
{
    /**
     * @param  Closure(PageContext): array<string, mixed>  $data  view data for the partial
     */
    public function __construct(
        public string $key,
        public string $view,
        public Closure $data,
        public int $order = 100,
    ) {}
}
