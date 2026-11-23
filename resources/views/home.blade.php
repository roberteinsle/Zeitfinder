<x-layouts.app robots="index,follow">
    <x-flash />

    <section class="grid gap-10 rounded-3xl bg-zinc-100 px-6 py-12 sm:px-12 sm:py-16 lg:grid-cols-[1fr_22rem] lg:items-center dark:bg-zinc-900">
        <div class="max-w-2xl">
            <p class="eyebrow">{{ __('Kostenlos · ohne Konto · ohne Werbung') }}</p>
            <h1 class="mt-6 text-4xl leading-[1.05] font-normal tracking-wide text-balance uppercase sm:text-6xl">{{ __('Den passenden Termin finden – ohne Hin und Her.') }}</h1>
            <p class="mt-6 max-w-xl text-lg text-zinc-700 text-pretty dark:text-zinc-300">{{ __('Termine vorschlagen, Link teilen, alle kreuzen an. Du siehst sofort, wann es am besten passt.') }}</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('polls.create') }}" class="btn-primary min-h-12 px-6 text-base">
                    <x-glyph name="plus" /> {{ __('Umfrage erstellen') }}
                </a>
                @guest
                    <a href="{{ route('login') }}" class="btn-secondary min-h-12 px-6 text-base">{{ __('Anmelden (optional)') }}</a>
                @endguest
            </div>
        </div>

        {{-- Illustration: how answers look, as colour cards --}}
        <div class="hidden gap-3 lg:grid" aria-hidden="true">
            @foreach ([
                ['bg-sage', __('Ja'), [[__('Di, 2. Dez.'), '4'], [__('Do, 4. Dez.'), '3']]],
                ['bg-blush', __('Wenn nötig'), [[__('Di, 2. Dez.'), '1'], [__('Do, 4. Dez.'), '2']]],
                ['bg-zinc-200 dark:bg-zinc-300', __('Nein'), [[__('Di, 2. Dez.'), '0'], [__('Do, 4. Dez.'), '1']]],
            ] as [$surface, $label, $rows])
                <div class="{{ $surface }} rounded-2xl p-5 text-zinc-950">
                    <p class="block border-b border-current pb-1.5 text-xs font-bold tracking-[.12em] uppercase">{{ $label }}</p>
                    <dl class="mt-3 space-y-1 text-sm">
                        @foreach ($rows as [$day, $count])
                            <div class="flex justify-between"><dt class="font-semibold">{{ $day }}</dt><dd>{{ $count }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-12 grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['calendar', 'bg-lavender', __('1. Termine vorschlagen'), __('Tage oder Zeitfenster eintragen – in Sekunden, auch am Handy.')],
            ['share', 'bg-mist', __('2. Link teilen'), __('Per Messenger oder Mail. Teilnehmende brauchen kein Konto.')],
            ['star', 'bg-blush', __('3. Termin festlegen'), __('Die Übersicht zeigt den besten Termin. Ein Klick, und alle werden benachrichtigt.')],
        ] as [$icon, $surface, $heading, $text])
            <div class="card p-6">
                <span class="{{ $surface }} grid size-10 place-items-center rounded-xl text-zinc-950"><x-glyph :name="$icon" /></span>
                <h2 class="mt-4 text-lg font-medium">{{ $heading }}</h2>
                <p class="mt-1.5 text-zinc-600 dark:text-zinc-400">{{ $text }}</p>
            </div>
        @endforeach
    </section>

    <section class="mt-12 card flex flex-col items-start gap-6 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
        <div>
            <h2 class="text-xl font-medium">{{ __('Ja, Nein oder „Wenn nötig“') }}</h2>
            <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ __('Drei klare Antworten statt endloser Nachrichten. Jede E-Mail-Adresse stimmt nur einmal ab.') }}</p>
        </div>
        <div class="flex gap-2">
            <span class="cell-yes inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium"><x-glyph name="check" class="size-4" />{{ __('Ja') }}</span>
            <span class="cell-maybe inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium"><x-glyph name="tilde" class="size-4" />{{ __('Wenn nötig') }}</span>
            <span class="cell-no inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium"><x-glyph name="x" class="size-4" />{{ __('Nein') }}</span>
        </div>
    </section>
</x-layouts.app>
