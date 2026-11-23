<?php

namespace App\Support\Registries;

/**
 * A public, indexable page. Used for sitemap.xml, llms.txt and IndexNow.
 */
final readonly class SitemapEntry
{
    public function __construct(
        public string $url,
        public string $title,
        public ?string $description = null,
        public string $changeFrequency = 'monthly',
        public float $priority = 0.5,
    ) {}
}
