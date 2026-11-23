{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($entries as $entry)
    <url>
        <loc>{{ $entry->url }}</loc>
        <changefreq>{{ $entry->changeFrequency }}</changefreq>
        <priority>{{ number_format($entry->priority, 1, '.', '') }}</priority>
    </url>
@endforeach
</urlset>
