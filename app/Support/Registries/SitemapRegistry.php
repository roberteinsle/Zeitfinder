<?php

namespace App\Support\Registries;

use Closure;

/**
 * Public pages that search engines may index. Each module registers its own pages;
 * only pages without "noindex" belong here (never polls, accounts or admin pages).
 */
final class SitemapRegistry
{
    /** @var array<string, Closure(): list<SitemapEntry>> */
    private array $sources = [];

    /**
     * @param  Closure(): list<SitemapEntry>  $entries  resolved lazily, so route() uses the forced root URL
     */
    public function register(string $key, Closure $entries): void
    {
        $this->sources[$key] = $entries;
    }

    /**
     * @return list<SitemapEntry>
     */
    public function entries(): array
    {
        $entries = [];
        foreach ($this->sources as $source) {
            array_push($entries, ...$source());
        }
        usort($entries, fn (SitemapEntry $a, SitemapEntry $b) => $b->priority <=> $a->priority);

        return $entries;
    }
}
