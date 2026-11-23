<?php

namespace App\Modules\Seo\Actions;

use App\Modules\Seo\Models\SeoSetting;
use App\Support\ActivityLogger;
use App\Support\Registries\SitemapRegistry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Sends all public URLs to IndexNow (Bing, Yandex, Seznam … share submissions).
 * Limited in the action itself, so neither the admin button nor saving can flood the API.
 */
final class SubmitToIndexNow
{
    public const ENDPOINT = 'https://api.indexnow.org/indexnow';

    private const LIMITER_KEY = 'seo:indexnow';

    private const LIMIT_SECONDS = 600;

    public function __construct(
        private readonly SitemapRegistry $sitemap,
        private readonly ActivityLogger $activity,
    ) {}

    public function __invoke(): IndexNowResult
    {
        $settings = SeoSetting::current();
        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! $settings->indexnow_enabled || ! $settings->indexing_enabled) {
            return IndexNowResult::Disabled;
        }

        // IndexNow only accepts public https hosts.
        if (! str_starts_with((string) config('app.url'), 'https://') || $host === '' || $host === 'localhost' || ! str_contains($host, '.')) {
            return IndexNowResult::NotPublic;
        }

        if (RateLimiter::tooManyAttempts(self::LIMITER_KEY, 1)) {
            return IndexNowResult::Throttled;
        }
        RateLimiter::hit(self::LIMITER_KEY, self::LIMIT_SECONDS);

        $urls = array_map(fn ($entry) => $entry->url, $this->sitemap->entries());

        try {
            $response = Http::timeout(10)->acceptJson()->post(self::ENDPOINT, [
                'host' => $host,
                'key' => $settings->indexnow_key,
                'keyLocation' => route('seo.indexnow-key', ['key' => $settings->indexnow_key]),
                'urlList' => $urls,
            ]);
            $status = $response->status();
        } catch (Throwable $e) {
            Log::error('IndexNow submission failed.', ['exception' => $e::class, 'message' => mb_substr($e->getMessage(), 0, 500)]);
            $status = 0;
        }

        $settings->forceFill(['indexnow_submitted_at' => now(), 'indexnow_status' => $status])->save();
        $this->activity->log('seo.indexnow', $settings, ['status' => $status, 'urls' => count($urls)]);

        if ($status === 200 || $status === 202) {
            return IndexNowResult::Submitted;
        }

        Log::warning('IndexNow rejected the submission.', ['status' => $status]);

        return IndexNowResult::Failed;
    }
}
