# {!! $settings->siteName() !!}

> {!! $settings->homeDescription() !!}

@if ($settings->llms_intro)
{!! $settings->llms_intro !!}

@endif
## Seiten

@foreach ($entries as $entry)
- [{!! $entry->title !!}]({!! $entry->url !!}){!! $entry->description ? ': '.$entry->description : '' !!}
@endforeach
