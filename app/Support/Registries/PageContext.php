<?php

namespace App\Support\Registries;

/**
 * What the layout knows about the page being rendered; passed to head contributions.
 */
final readonly class PageContext
{
    public function __construct(
        public ?string $routeName,
        public ?string $title,
        public string $robots,
    ) {}

    public function isIndexable(): bool
    {
        return ! str_contains($this->robots, 'noindex');
    }
}
