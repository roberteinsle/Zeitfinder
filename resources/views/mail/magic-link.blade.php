<x-mail::message>
# {{ __('Hallo!') }}

{{ __('Klicke auf den Knopf, um dich bei :app anzumelden. Der Link ist :minutes Minuten gültig und funktioniert nur einmal.', ['app' => config('app.name'), 'minutes' => $minutes]) }}

<x-mail::button :url="$url">
{{ __('Jetzt anmelden') }}
</x-mail::button>

{{ __('Falls du das nicht angefordert hast, ignoriere diese E-Mail einfach.') }}
</x-mail::message>
