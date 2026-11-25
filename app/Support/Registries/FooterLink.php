<?php

namespace App\Support\Registries;

use Closure;

/**
 * A link in the footer of the public layout, contributed by a module.
 */
final readonly class FooterLink
{
    /**
     * @param  Closure(): ?string  $url  null hides the link
     */
    public function __construct(
        public string $key,
        public string $label,
        public Closure $url,
        public int $order = 100,
    ) {}
}
