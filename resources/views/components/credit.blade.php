@php
    $name = (string) config('zeitfinder.credit.name');
    $url = (string) config('zeitfinder.credit.url');
    $place = (string) config('zeitfinder.credit.place');
@endphp
@if ($name !== '')
    <span lang="en" class="text-center">
        {{ $place !== '' ? __('Made with ♥️ in :place by', ['place' => $place]) : __('Made with ♥️ by') }}
        @if (str_starts_with($url, 'https://'))
            <a href="{{ $url }}" rel="noopener" class="underline underline-offset-2 hover:text-zinc-800 dark:hover:text-zinc-200">{{ $name }}</a>
        @else
            {{ $name }}
        @endif
    </span>
@endif
