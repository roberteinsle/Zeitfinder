<x-mail::message>
# {{ __('Der Termin steht!') }}

**{{ $poll->title }}**

<x-mail::panel>
{{ $option->fullLabel() }}
@if ($poll->location)

{{ __('Ort: :location', ['location' => $poll->location]) }}
@endif
</x-mail::panel>

{{ __('Den Kalendereintrag findest du im Anhang.') }}

<x-mail::button :url="$pollUrl">
{{ __('Zur Umfrage') }}
</x-mail::button>
</x-mail::message>
