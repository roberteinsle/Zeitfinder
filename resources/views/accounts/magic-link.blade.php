<x-layouts.app :title="__('Anmelden')">
    <div class="mx-auto max-w-md">
        <div class="card p-6 text-center sm:p-8">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-brand-50 text-ink dark:bg-brand-900/50"><x-glyph name="mail" class="size-6" /></span>
            <h1 class="mt-4 text-2xl font-medium">{{ __('Anmeldung bestätigen') }}</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Klicke auf den Knopf, um dich anzumelden.') }}</p>
            <form method="POST" action="{{ route('magic-link.redeem', ['token' => $token]) }}" class="mt-6">
                @csrf
                <button type="submit" class="btn-primary w-full">{{ __('Jetzt anmelden') }}</button>
            </form>
        </div>
    </div>
</x-layouts.app>
