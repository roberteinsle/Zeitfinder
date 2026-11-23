<x-layouts.app :title="__('Passkey bestätigen')">
    <div class="mx-auto max-w-md">
        <div class="card p-6 text-center sm:p-8" data-passkey-scope>
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-brand-50 text-ink dark:bg-brand-900/50"><x-glyph name="key" class="size-6" /></span>
            <h1 class="mt-4 text-2xl font-medium">{{ __('Passkey erforderlich') }}</h1>

            @if ($hasPasskeys)
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Bestätige diese Sitzung mit deinem Passkey, um den Admin-Bereich zu öffnen.') }}</p>
                <button type="button" class="btn-primary mt-6 w-full" data-passkey-confirm data-passkey-supported hidden>{{ __('Mit Passkey bestätigen') }}</button>
            @else
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Für den Admin-Bereich brauchst du einen Passkey. Richte jetzt einen ein – danach bestätigst du ihn direkt.') }}</p>
                <input id="passkey-name" type="text" maxlength="60" placeholder="{{ __('Name, z. B. „Mein Laptop“') }}" class="input mt-6" aria-label="{{ __('Name des Passkeys') }}" data-passkey-supported hidden>
                <button type="button" class="btn-primary mt-3 w-full" data-passkey-register="passkey-name" data-then="confirm" data-passkey-supported hidden>{{ __('Passkey einrichten') }}</button>
            @endif
            <p class="mt-4 text-sm text-zinc-500" data-passkey-unsupported hidden>{{ __('Dieser Browser unterstützt keine Passkeys.') }}</p>
            <p class="field-error" data-passkey-error hidden></p>
        </div>
    </div>
</x-layouts.app>
