<address class="break-words whitespace-pre-line not-italic">{{ $legal->operator_name }}
{{ $legal->operator_address }}</address>
<p class="break-words">
    {{ __('E-Mail') }}: <a href="mailto:{{ $legal->contact_email }}" class="underline underline-offset-2">{{ $legal->contact_email }}</a>
    @if ($legal->contact_phone)
        <br>{{ __('Telefon') }}: {{ $legal->contact_phone }}
    @endif
</p>
