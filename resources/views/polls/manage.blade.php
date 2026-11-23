@php
    use App\Support\DisplayTime;
    $ranked = $results->ranked();
    $participantCount = $poll->participants->count();
@endphp
<x-layouts.app :title="__('Verwalten: :title', ['title' => $poll->title])">
    <x-flash />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-medium text-brand-600 dark:text-brand-400">{{ __('Umfrage verwalten') }}</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight break-words">{{ $poll->title }}</h1>
            <p class="mt-2 text-sm text-zinc-500">{{ $poll->status->label() }} · {{ trans_choice(':count Teilnahme|:count Teilnahmen', $participantCount, ['count' => $participantCount]) }}</p>
        </div>
        <a href="{{ route('polls.show', $poll) }}" class="btn-secondary shrink-0">{{ __('Zur Umfrage') }}</a>
    </div>

    <div class="mt-6 flex flex-col gap-2 sm:flex-row">
        <input id="share-url" readonly value="{{ route('polls.show', $poll) }}" class="input font-mono" aria-label="{{ __('Link zur Umfrage') }}">
        <button type="button" class="btn-secondary shrink-0" data-copy="share-url" data-copied="{{ __('Kopiert!') }}"><x-glyph name="link" class="size-4" /><span data-copy-label>{{ __('Link kopieren') }}</span></button>
        <button type="button" class="btn-secondary shrink-0" data-share="{{ route('polls.show', $poll) }}" data-share-title="{{ $poll->title }}"><x-glyph name="share" class="size-4" />{{ __('Teilen') }}</button>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-5">
        {{-- Finalize --}}
        <section class="card p-5 sm:p-6 lg:col-span-3">
            @if ($poll->isFinalized() && $poll->finalOption)
                <h2 class="text-lg font-semibold">{{ __('Der Termin steht fest') }}</h2>
                <p class="mt-3 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">{{ $poll->finalOption->fullLabel() }}</p>
                <p class="mt-1 text-sm text-zinc-500">{{ __('Festgelegt am :date. Alle Teilnehmenden wurden per E-Mail informiert.', ['date' => DisplayTime::dateTime($poll->finalized_at ?? now())]) }}</p>
                <form method="POST" action="{{ route('polls.reopen', $poll) }}" class="mt-5" data-confirm="{{ __('Abstimmung wieder öffnen? Der festgelegte Termin wird zurückgenommen.') }}">
                    @csrf
                    <button class="btn-secondary">{{ __('Abstimmung wieder öffnen') }}</button>
                </form>
            @else
                <h2 class="text-lg font-semibold">{{ __('Finalen Termin festlegen') }}</h2>
                <p class="mt-1 text-sm text-zinc-500">{{ __('Sortiert nach Zusagen. Nach dem Festlegen bekommen alle eine E-Mail mit Kalendereintrag.') }}</p>
                <form method="POST" action="{{ route('polls.finalize', $poll) }}" class="mt-4" data-confirm="{{ __('Diesen Termin festlegen und alle benachrichtigen?') }}">
                    @csrf
                    <div class="space-y-2">
                        @foreach ($ranked as $i => $option)
                            @php $c = $results->counts[$option->id]; @endphp
                            <label @class(['flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:has-[:checked]:bg-brand-950/50', 'border-zinc-200 dark:border-zinc-800'])>
                                <input type="radio" name="option_id" value="{{ $option->id }}" class="size-4 accent-brand-600" @checked($i === 0) required>
                                <span class="flex-1">
                                    <span class="block font-medium">{{ DisplayTime::weekday($option->starts_at) }}, {{ DisplayTime::date($option->starts_at) }} · {{ $option->timeLabel() }}</span>
                                    <span class="block text-sm text-zinc-500">
                                        <span class="text-emerald-600 dark:text-emerald-400">{{ $c['yes'] }} {{ __('Ja') }}</span>
                                        @if ($poll->allow_maybe) · <span class="text-amber-600 dark:text-amber-400">{{ $c['maybe'] }} {{ __('wenn nötig') }}</span>@endif
                                        · {{ $c['no'] }} {{ __('Nein') }}
                                    </span>
                                </span>
                                @if ($results->isBest($option))
                                    <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-semibold text-brand-700 dark:bg-brand-900 dark:text-brand-200">★ {{ __('Top') }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" class="btn-primary mt-5 w-full sm:w-auto"><x-glyph name="check" class="size-4" />{{ __('Termin festlegen & alle benachrichtigen') }}</button>
                </form>
            @endif
        </section>

        {{-- Participants --}}
        <section class="card p-5 sm:p-6 lg:col-span-2">
            <h2 class="text-lg font-semibold">{{ __('Teilnehmende') }}</h2>
            @if ($participantCount === 0)
                <p class="mt-3 text-sm text-zinc-500">{{ __('Noch niemand hat abgestimmt.') }}</p>
            @else
                <ul class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($poll->participants as $participant)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ $participant->name }}</p>
                                <p class="truncate text-sm text-zinc-500">{{ $participant->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('polls.participants.destroy', [$poll, $participant]) }}" data-confirm="{{ __(':name entfernen?', ['name' => $participant->name]) }}">
                                @csrf @method('DELETE')
                                <button class="btn-ghost size-10 p-0 text-zinc-400 hover:text-red-600" title="{{ __('Entfernen') }}"><x-glyph name="trash" class="size-4" /><span class="sr-only">{{ __('Entfernen') }}</span></button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    @if ($participantCount > 0)
        <section class="mt-8">
            <h2 class="text-xl font-semibold">{{ __('Übersicht') }}</h2>
            @include('polls.partials.results-table', ['poll' => $poll, 'results' => $results, 'me' => null])
        </section>
    @endif

    {{-- Edit --}}
    <section class="mt-10">
        <h2 class="text-xl font-semibold">{{ __('Umfrage bearbeiten') }}</h2>
        <form method="POST" action="{{ route('polls.manage.update', $poll) }}" class="mt-4 space-y-6" novalidate>
            @csrf @method('PUT')

            <div class="card space-y-5 p-5 sm:p-6">
                <div>
                    <label for="title" class="label">{{ __('Titel') }}</label>
                    <input id="title" name="title" type="text" maxlength="120" required value="{{ old('title', $poll->title) }}" class="input @error('title') input-error @enderror">
                    <x-field-error name="title" />
                </div>
                <div>
                    <label for="location" class="label">{{ __('Ort') }}</label>
                    <input id="location" name="location" type="text" maxlength="200" value="{{ old('location', $poll->location) }}" class="input @error('location') input-error @enderror">
                    <x-field-error name="location" />
                </div>
                <div>
                    <label for="description" class="label">{{ __('Beschreibung') }}</label>
                    <textarea id="description" name="description" rows="3" maxlength="2000" class="input @error('description') input-error @enderror">{{ old('description', $poll->description) }}</textarea>
                    <x-field-error name="description" />
                </div>
                <div class="space-y-3">
                    <label class="flex items-center gap-3">
                        <input type="hidden" name="allow_maybe" value="0">
                        <input type="checkbox" name="allow_maybe" value="1" class="checkbox" @checked(old('allow_maybe', $poll->allow_maybe ? '1' : '0') === '1')>
                        <span class="font-medium">{{ __('„Wenn nötig“ erlauben') }}</span>
                    </label>
                    <label class="flex items-center gap-3">
                        <input type="hidden" name="notify_on_vote" value="0">
                        <input type="checkbox" name="notify_on_vote" value="1" class="checkbox" @checked(old('notify_on_vote', $poll->notify_on_vote ? '1' : '0') === '1')>
                        <span class="font-medium">{{ __('Bei jeder Antwort benachrichtigen') }}</span>
                    </label>
                </div>
            </div>

            <div class="card p-5 sm:p-6">
                <h3 class="font-semibold">{{ __('Termine') }}</h3>
                <ul class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($poll->options as $option)
                        <li class="flex items-center justify-between gap-3 py-2">
                            <span>{{ $option->fullLabel() }}</span>
                            @if ($poll->final_option_id !== $option->id)
                                <label class="flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                    <input type="checkbox" name="remove_options[]" value="{{ $option->id }}" class="checkbox accent-red-600">
                                    {{ __('entfernen') }}
                                </label>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <x-field-error name="new_options" />

                <h3 class="mt-6 mb-3 font-semibold">{{ __('Termine hinzufügen') }}</h3>
                @include('polls.partials.option-rows', ['name' => 'new_options', 'rows' => old('new_options', []), 'minOne' => false])
            </div>

            <button type="submit" class="btn-primary">{{ __('Änderungen speichern') }}</button>
        </form>
    </section>

    <section class="mt-12 rounded-2xl border border-red-200 p-5 sm:p-6 dark:border-red-900">
        <h2 class="font-semibold text-red-700 dark:text-red-400">{{ __('Umfrage löschen') }}</h2>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Löscht die Umfrage mit allen Antworten. Das lässt sich nicht rückgängig machen.') }}</p>
        <form method="POST" action="{{ route('polls.destroy', $poll) }}" class="mt-4" data-confirm="{{ __('Umfrage endgültig löschen?') }}">
            @csrf @method('DELETE')
            <button class="btn-danger"><x-glyph name="trash" class="size-4" />{{ __('Endgültig löschen') }}</button>
        </form>
    </section>
</x-layouts.app>
