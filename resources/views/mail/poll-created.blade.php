<x-mail::message>
# {{ __('Deine Umfrage ist bereit') }}

**{{ $poll->title }}**

{{ __('Teile diesen Link mit allen, die mitmachen sollen:') }}

<x-mail::panel>
{{ $publicUrl }}
</x-mail::panel>

{{ __('Mit deinem persönlichen Verwaltungslink bearbeitest du die Umfrage und legst den finalen Termin fest. Gib ihn nicht weiter.') }}

<x-mail::button :url="$adminUrl">
{{ __('Umfrage verwalten') }}
</x-mail::button>
</x-mail::message>
