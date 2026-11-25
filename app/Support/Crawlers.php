<?php

namespace App\Support;

/**
 * Known search engine, SEO and AI crawlers (product tokens). Used to keep private pages
 * away from them: named in robots.txt and refused by the Polls module.
 *
 * Link previews of messengers (facebookexternalhit, WhatsApp, Slackbot, Twitterbot …) are
 * deliberately missing, so shared poll links still show a preview. Tokens must be specific
 * enough not to match ordinary browsers.
 */
final class Crawlers
{
    public const TOKENS = [
        // Search engines (AdsBot ignores "User-agent: *" in robots.txt, so it must be named)
        'Googlebot', 'GoogleOther', 'Google-InspectionTool', 'Storebot-Google', 'AdsBot-Google', 'Mediapartners-Google',
        'bingbot', 'BingPreview', 'adidxbot', 'Slurp', 'DuckDuckBot', 'YandexBot', 'Baiduspider', 'Applebot', 'Qwantbot',
        'SeznamBot', 'Sogou web spider', 'PetalBot', 'MojeekBot', 'Kagibot', 'Yeti', 'coccocbot', 'archive.org_bot', 'ia_archiver',
        // AI training, AI search and AI assistants fetching pages for a user
        'GPTBot', 'ChatGPT-User', 'OAI-SearchBot', 'ClaudeBot', 'Claude-Web', 'Claude-User', 'Claude-SearchBot', 'anthropic-ai',
        'Google-CloudVertexBot', 'Gemini-Deep-Research', 'PerplexityBot', 'Perplexity-User', 'MistralAI-User', 'DuckAssistBot',
        'meta-externalagent', 'meta-externalfetcher', 'FacebookBot', 'Amazonbot', 'Bytespider', 'TikTokSpider', 'CCBot',
        'cohere-ai', 'cohere-training-data-crawler', 'AI2Bot', 'Diffbot', 'YouBot', 'Timpibot', 'Omgilibot', 'webzio-extended',
        'ImagesiftBot', 'PanguBot', 'FirecrawlAgent',
        // SEO indexes
        'AhrefsBot', 'SemrushBot', 'MJ12bot', 'DotBot', 'DataForSeoBot', 'BLEXBot',
    ];

    /** Only meaningful in robots.txt: these opt-out tokens never appear as a User-Agent header. */
    public const ROBOTS_ONLY = ['Google-Extended', 'Applebot-Extended'];

    /** Value of X-Robots-Tag for private pages. */
    public const PRIVATE_PAGE = 'noindex, nofollow, noarchive, nosnippet, noimageindex, noai, noimageai';

    public static function isCrawler(?string $userAgent): bool
    {
        if ($userAgent === null || $userAgent === '') {
            return false;
        }

        foreach (self::TOKENS as $token) {
            if (stripos($userAgent, $token) !== false) {
                return true;
            }
        }

        return false;
    }
}
