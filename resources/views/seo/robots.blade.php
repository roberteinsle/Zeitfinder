User-agent: *
@if ($settings->indexing_enabled)
Disallow: /admin
Disallow: /u/
Disallow: /konto
Disallow: /anmelden
@if ($settings->robots_extra)
{!! $settings->robots_extra !!}
@endif

Sitemap: {!! route('seo.sitemap') !!}
@else
Disallow: /
@endif
