<?php

namespace App\Modules\Seo\Http\Controllers;

use App\Modules\Seo\Models\SeoSetting;
use App\Support\Registries\SitemapRegistry;
use Illuminate\Http\Response;

/**
 * Crawler files. robots.txt and llms.txt are plain text (served with nosniff), so their
 * views print admin-edited text raw: Blade escaping would add HTML entities.
 */
final class SeoController
{
    public function sitemap(SitemapRegistry $sitemap): Response
    {
        $entries = SeoSetting::current()->indexing_enabled ? $sitemap->entries() : [];

        return response()
            ->view('seo.sitemap', ['entries' => $entries])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $settings = SeoSetting::current();

        return response()
            ->view('seo.robots', ['settings' => $settings])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function llms(SitemapRegistry $sitemap): Response
    {
        $settings = SeoSetting::current();
        abort_unless($settings->indexing_enabled, 404);

        return response()
            ->view('seo.llms', ['settings' => $settings, 'entries' => $sitemap->entries()])
            ->header('Content-Type', 'text/markdown; charset=UTF-8');
    }

    /** IndexNow verifies ownership by fetching https://<host>/<key>.txt. */
    public function indexNowKey(string $key): Response
    {
        $settings = SeoSetting::current();
        abort_unless($settings->indexnow_enabled && hash_equals($settings->indexnow_key, $key), 404);

        return response($settings->indexnow_key, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
