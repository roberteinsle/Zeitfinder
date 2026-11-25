User-agent: *
@if ($settings->indexing_enabled)
Disallow: /admin
Disallow: /u/
Disallow: /konto
Disallow: /anmelden
@if ($settings->robots_extra)
{!! $settings->robots_extra !!}
@endif

@foreach ([...\App\Support\Crawlers::TOKENS, ...\App\Support\Crawlers::ROBOTS_ONLY] as $token)
User-agent: {!! $token !!}
@endforeach
Disallow: /admin
Disallow: /u/
Disallow: /konto
Disallow: /anmelden

Sitemap: {!! route('seo.sitemap') !!}
@else
Disallow: /
@endif
