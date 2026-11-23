<x-layouts.app :title="__('Konto')">
    <x-flash />

    @if (session('suggest_passkey'))
        <div class="card mb-8 flex flex-col gap-4 border-brand-200 bg-brand-50 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-brand-900 dark:bg-brand-950/50" data-passkey-scope data-passkey-supported hidden>
            <div>
                <p class="flex items-center gap-2 font-semibold"><x-glyph name="key" class="size-5 text-brand-600" />{{ __('Nächstes Mal ohne E-Mail anmelden?') }}</p>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Richte einen Passkey ein – Anmeldung per Face ID oder Fingerabdruck in einer Sekunde.') }}</p>
                <p class="field-error" data-passkey-error hidden></p>
            </div>
            <button type="button" class="btn-primary shrink-0" data-passkey-register>{{ __('Passkey einrichten') }}</button>
        </div>
    @endif

    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-zinc-500">{{ __('Angemeldet als') }}</p>
            <h1 class="text-3xl font-semibold tracking-tight">{{ $user->name }}</h1>
            <p class="text-sm text-zinc-500">{{ $user->email }}</p>
        </div>
        <div class="mt-3 flex gap-2 sm:mt-0">
            @if ($user->is_admin)
                <a href="/admin" class="btn-secondary">{{ __('Administration') }}</a>
            @endif
            <a href="{{ route('account.security') }}" class="btn-secondary"><x-glyph name="cog" class="size-4" />{{ __('Sicherheit') }}</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-ghost"><x-glyph name="logout" class="size-4" />{{ __('Abmelden') }}</button>
            </form>
        </div>
    </div>

    <div class="mt-10 space-y-12">
        @foreach ($sections as $section)
            @include($section['view'], $section['data'])
        @endforeach
    </div>
</x-layouts.app>
