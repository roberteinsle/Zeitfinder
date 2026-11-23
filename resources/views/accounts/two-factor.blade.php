<x-layouts.app :title="__('Zwei-Faktor-Code')">
    <div class="mx-auto max-w-md">
        <div class="card p-6 sm:p-8">
            <h1 class="text-2xl font-medium">{{ __('Zwei-Faktor-Code') }}</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Gib den 6-stelligen Code aus deiner Authenticator-App ein.') }}</p>
            <form method="POST" action="{{ route('login.two-factor.verify') }}" class="mt-6 space-y-4">
                @csrf
                <input name="code" type="text" inputmode="numeric" pattern="[0-9 ]*" maxlength="10" autocomplete="one-time-code" required autofocus
                       class="input text-center font-mono text-2xl tracking-[.4em] @error('code') input-error @enderror" aria-label="{{ __('Code') }}">
                <x-field-error name="code" />
                <button type="submit" class="btn-primary w-full">{{ __('Anmelden') }}</button>
            </form>
        </div>
    </div>
</x-layouts.app>
