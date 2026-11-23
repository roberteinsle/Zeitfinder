<section class="card p-5 sm:p-6" data-passkey-scope>
    <h2 class="flex items-center gap-2 font-medium"><x-glyph name="key" class="size-5 text-ink" />{{ __('Mit Passkey') }}</h2>
    <p class="mt-1 text-sm text-zinc-500">{{ __('Face ID, Fingerabdruck oder Sicherheitsschlüssel – am schnellsten und sichersten.') }}</p>
    <button type="button" class="btn-secondary mt-4 w-full" data-passkey-login data-passkey-supported hidden><x-glyph name="key" class="size-4" />{{ __('Mit Passkey anmelden') }}</button>
    <p class="mt-4 text-sm text-zinc-500" data-passkey-unsupported hidden>{{ __('Dieser Browser unterstützt keine Passkeys.') }}</p>
    <p class="field-error" data-passkey-error data-message="{{ __('Anmeldung mit Passkey fehlgeschlagen. Bitte versuche es erneut.') }}" hidden></p>
    <x-field-error name="credential" />
</section>
