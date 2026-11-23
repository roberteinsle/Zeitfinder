<x-layouts.app :title="__('Umfrage bereit')">
    <div class="mx-auto max-w-2xl">
        <div class="card p-6 text-center sm:p-10">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-success-100 text-success-600 dark:bg-success-500/20 dark:text-success-300"><x-glyph name="check" class="size-7" /></span>
            <h1 class="mt-5 text-2xl font-medium tracking-tight sm:text-3xl">{{ __('Deine Umfrage ist bereit!') }}</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ __('Teile diesen Link mit allen, die mitmachen sollen.') }}</p>

            <div class="mt-6 flex flex-col gap-2 sm:flex-row">
                <input id="share-url" readonly value="{{ route('polls.show', $poll) }}" class="input text-center font-mono sm:text-left" aria-label="{{ __('Link zur Umfrage') }}">
                <button type="button" class="btn-primary shrink-0" data-copy="share-url" data-copied="{{ __('Kopiert!') }}"><x-glyph name="link" class="size-4" /><span data-copy-label>{{ __('Kopieren') }}</span></button>
            </div>
            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <button type="button" class="btn-secondary" data-share="{{ route('polls.show', $poll) }}" data-share-title="{{ $poll->title }}"><x-glyph name="share" class="size-4" />{{ __('Teilen') }}</button>
                <a class="btn-secondary" href="{{ route('polls.show', $poll) }}">{{ __('Zur Umfrage') }}</a>
            </div>
        </div>

        <div class="card mt-6 p-6">
            <h2 class="flex items-center gap-2 font-medium"><x-glyph name="lock" class="size-5 text-ink" />{{ __('Dein Verwaltungslink') }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Damit bearbeitest du die Umfrage und legst den finalen Termin fest. Nicht weitergeben! Wir haben ihn dir auch per E-Mail geschickt.') }}</p>
            @if ($adminUrl)
                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <input id="admin-url" readonly value="{{ $adminUrl }}" class="input font-mono text-xs" aria-label="{{ __('Verwaltungslink') }}">
                    <button type="button" class="btn-secondary shrink-0" data-copy="admin-url" data-copied="{{ __('Kopiert!') }}"><span data-copy-label>{{ __('Kopieren') }}</span></button>
                </div>
            @endif
            <a href="{{ route('polls.manage', $poll) }}" class="btn-ghost mt-3 -ml-3"><x-glyph name="cog" class="size-4" />{{ __('Umfrage verwalten') }}</a>
        </div>
    </div>
</x-layouts.app>
