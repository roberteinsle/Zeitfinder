@php
    $indexable = $settings->indexing_enabled && $page->isIndexable();
    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => array_values(array_filter([
            [
                '@type' => 'WebSite',
                'name' => $settings->siteName(),
                'url' => route('home'),
                'description' => $settings->homeDescription(),
                'inLanguage' => 'de-DE',
            ],
            [
                '@type' => 'WebApplication',
                'name' => $settings->siteName(),
                'url' => route('home'),
                'description' => $settings->homeDescription(),
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'inLanguage' => 'de-DE',
                'isAccessibleForFree' => true,
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'],
            ],
            $settings->organization_name ? [
                '@type' => 'Organization',
                'name' => $settings->organization_name,
                'url' => route('home'),
            ] : null,
        ])),
    ];
@endphp
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="{{ $robots }}">
@if ($indexable)
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $settings->siteName() }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="de_DE">
    <meta name="twitter:card" content="summary">
@endif
@if ($indexable && $isHome)
    {{-- JSON data block (not executed); JSON_HEX_TAG keeps "</script>" out of the content. --}}
    <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR) !!}</script>
@endif
