<x-mail::message>
# {{ __('Hallo :name,', ['name' => $name]) }}

@if ($duplicate)
{{ __('Mit dieser E-Mail-Adresse wurde bei „:title“ bereits abgestimmt. Wenn du das warst, kannst du deine Antwort über den Knopf ändern. Falls nicht, kannst du diese E-Mail ignorieren.', ['title' => $poll->title]) }}
@else
{{ __('danke für deine Antwort bei „:title“. Über diesen persönlichen Link kannst du sie jederzeit ändern:', ['title' => $poll->title]) }}
@endif

<x-mail::button :url="$editUrl">
{{ __('Antwort ändern') }}
</x-mail::button>

{{ __('Wenn der Termin feststeht, bekommst du eine E-Mail.') }}
</x-mail::message>
