<x-mail::message>
# {{ $updated ? __(':name hat die Antwort geändert', ['name' => $participantName]) : __(':name hat abgestimmt', ['name' => $participantName]) }}

{{ __('Umfrage: :title', ['title' => $poll->title]) }}

<x-mail::button :url="$manageUrl">
{{ __('Ergebnis ansehen') }}
</x-mail::button>
</x-mail::message>
