<x-layouts.app robots="index,follow">
    <x-flash />

    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 px-6 py-14 text-white shadow-xl shadow-brand-900/20 sm:px-12 sm:py-20">
        <div class="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-16 size-80 rounded-full bg-fuchsia-400/20 blur-3xl"></div>

        <div class="relative max-w-2xl">
            <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-sm font-medium backdrop-blur">
                <x-glyph name="check" class="size-4" /> {{ __('Kostenlos · ohne Konto · ohne Werbung') }}
            </p>
            <h1 class="text-4xl font-semibold tracking-tight text-balance sm:text-5xl">{{ __('Den passenden Termin finden – ohne Hin und Her.') }}</h1>
            <p class="mt-5 text-lg text-brand-100 text-pretty">{{ __('Termine vorschlagen, Link teilen, alle kreuzen an. Du siehst sofort, wann es am besten passt.') }}</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('polls.create') }}" class="btn min-h-12 bg-white px-6 text-base text-brand-700 shadow-lg hover:bg-brand-50">
                    <x-glyph name="plus" /> {{ __('Umfrage erstellen') }}
                </a>
                @guest
                    <a href="{{ route('login') }}" class="btn min-h-12 px-6 text-base text-white ring-1 ring-white/40 hover:bg-white/10">{{ __('Anmelden (optional)') }}</a>
                @endguest
            </div>
        </div>
    </section>

    <section class="mt-12 grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['calendar', __('1. Termine vorschlagen'), __('Tage oder Zeitfenster eintragen – in Sekunden, auch am Handy.')],
            ['share', __('2. Link teilen'), __('Per Messenger oder Mail. Teilnehmende brauchen kein Konto.')],
            ['star', __('3. Termin festlegen'), __('Die Übersicht zeigt den besten Termin. Ein Klick, und alle werden benachrichtigt.')],
        ] as [$icon, $heading, $text])
            <div class="card p-6">
                <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/50 dark:text-brand-300"><x-glyph :name="$icon" /></span>
                <h2 class="mt-4 font-semibold">{{ $heading }}</h2>
                <p class="mt-1.5 text-sm text-zinc-600 dark:text-zinc-400">{{ $text }}</p>
            </div>
        @endforeach
    </section>

    <section class="mt-12 card flex flex-col items-start gap-6 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
        <div>
            <h2 class="text-lg font-semibold">{{ __('Ja, Nein oder „Wenn nötig“') }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Drei klare Antworten statt endloser Nachrichten. Jede E-Mail-Adresse stimmt nur einmal ab.') }}</p>
        </div>
        <div class="flex gap-2">
            <span class="cell-yes inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium"><x-glyph name="check" class="size-4" />{{ __('Ja') }}</span>
            <span class="cell-maybe inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium"><x-glyph name="tilde" class="size-4" />{{ __('Wenn nötig') }}</span>
            <span class="cell-no inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium"><x-glyph name="x" class="size-4" />{{ __('Nein') }}</span>
        </div>
    </section>
</x-layouts.app>
