@php
    use App\Modules\Polls\Enums\Answer;
    use App\Support\DisplayTime;
    $participantCount = $poll->participants->count();
    $ranked = array_slice($results->ranked(), 0, 3);
@endphp
<x-layouts.app :title="$poll->title">
    <x-flash />

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-medium text-ink">{{ __('Terminumfrage von :name', ['name' => $poll->organizer_name]) }}</p>
            <h1 class="mt-1 text-3xl font-medium tracking-tight break-words">{{ $poll->title }}</h1>
            <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                @if ($poll->location)
                    <span class="inline-flex items-center gap-1.5"><x-glyph name="map" class="size-4" />{{ $poll->location }}</span>
                @endif
                <span class="inline-flex items-center gap-1.5"><x-glyph name="users" class="size-4" />{{ trans_choice(':count Teilnahme|:count Teilnahmen', $participantCount, ['count' => $participantCount]) }}</span>
                <span class="inline-flex items-center gap-1.5"><x-glyph name="calendar" class="size-4" />{{ trans_choice(':count Termin|:count Termine', $poll->options->count(), ['count' => $poll->options->count()]) }}</span>
            </div>
        </div>
        @if ($canManage)
            <a href="{{ route('polls.manage', $poll) }}" class="btn-primary shrink-0"><x-glyph name="cog" class="size-4" />{{ __('Verwalten') }}</a>
        @endif
    </div>

    @if ($poll->description)
        <p class="mt-5 max-w-3xl whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $poll->description }}</p>
    @endif

    {{-- Final date --}}
    @if ($poll->isFinalized() && $poll->finalOption)
        <section class="mt-8 overflow-hidden rounded-2xl bg-eucalyptus p-6 text-night sm:p-8">
            <p class="eyebrow">{{ __('Der Termin steht fest') }}</p>
            <p class="mt-3 font-display text-2xl font-medium sm:text-3xl">{{ DisplayTime::longDate($poll->finalOption->starts_at) }}</p>
            <p class="mt-1 text-lg">{{ $poll->finalOption->timeLabel() }}</p>
            <a href="{{ route('polls.calendar', $poll) }}" class="btn mt-5 bg-night text-white hover:bg-night/85"><x-glyph name="download" class="size-4" />{{ __('In den Kalender') }}</a>
        </section>
    @elseif ($participantCount > 0 && $results->bestOptionIds !== [])
        {{-- Best options so far --}}
        <section class="mt-8">
            <h2 class="text-sm font-medium tracking-wide text-zinc-500 uppercase">{{ __('Passt bisher am besten') }}</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                @foreach ($ranked as $option)
                    @php $c = $results->counts[$option->id]; @endphp
                    <div @class(['card p-4', 'ring-2 ring-ink' => $results->isBest($option)])>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold">{{ DisplayTime::weekday($option->starts_at) }}, {{ DisplayTime::date($option->starts_at) }}</p>
                                <p class="text-sm text-zinc-500">{{ $option->timeLabel() }}</p>
                            </div>
                            @if ($results->isBest($option))
                                <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-ink dark:bg-brand-900/60"><x-glyph name="star" class="size-3.5" />{{ __('Top') }}</span>
                            @endif
                        </div>
                        @php $yesW = $participantCount ? round($c['yes'] / $participantCount * 100, 1) : 0; @endphp
                        @php $maybeW = $participantCount ? round($c['maybe'] / $participantCount * 100, 1) : 0; @endphp
                        <svg class="mt-3 h-2 w-full overflow-hidden rounded-full" viewBox="0 0 100 2" preserveAspectRatio="none" role="img" aria-label="{{ __(':percent % können', ['percent' => $results->availability($option->id)]) }}">
                            <rect width="100" height="2" class="fill-zinc-100 dark:fill-zinc-800" />
                            <rect width="{{ $yesW }}" height="2" class="fill-success-500" />
                            <rect x="{{ $yesW }}" width="{{ $maybeW }}" height="2" class="fill-maybe-400" />
                        </svg>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <span class="font-medium text-success-600 dark:text-success-400">{{ $c['yes'] }} {{ __('Ja') }}</span>
                            @if ($poll->allow_maybe) · <span class="text-maybe-600 dark:text-maybe-400">{{ $c['maybe'] }} {{ __('wenn nötig') }}</span>@endif
                            · {{ $c['no'] }} {{ __('Nein') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Vote form --}}
    @if ($poll->isOpen())
        <section class="mt-10" id="abstimmen">
            <h2 class="text-xl font-medium">{{ $me ? __('Deine Antwort ändern') : __('Wann kannst du?') }}</h2>

            <form method="POST" action="{{ $me ? route('polls.vote.update', $poll) : route('polls.vote', $poll) }}" class="mt-4 space-y-4">
                @csrf
                @if ($me) @method('PUT') @endif

                <div class="card grid gap-4 p-4 sm:grid-cols-2 sm:p-5">
                    <div>
                        <label for="name" class="label">{{ __('Dein Name') }}</label>
                        <input id="name" name="name" type="text" maxlength="80" required autocomplete="name"
                               value="{{ old('name', $me?->name ?? auth()->user()?->displayNameForOthers()) }}" class="input @error('name') input-error @enderror">
                        <x-field-error name="name" />
                    </div>
                    @if (! $me)
                        @auth
                            <div>
                                <span class="label">{{ __('E-Mail') }}</span>
                                <p class="flex min-h-11 items-center text-sm text-zinc-600 dark:text-zinc-400">{{ auth()->user()->email }}</p>
                                @include('polls.partials.email-hint')
                            </div>
                        @else
                            <div>
                                <label for="email" class="label">{{ __('Deine E-Mail') }}</label>
                                <input id="email" name="email" type="email" maxlength="255" required autocomplete="email"
                                       value="{{ old('email') }}" class="input @error('email') input-error @enderror">
                                <x-field-error name="email" />
                                @include('polls.partials.email-hint')
                            </div>
                        @endauth
                    @endif
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm text-zinc-500">{{ __('Tippe für jeden Termin deine Antwort an.') }}</p>
                    <div class="flex gap-1">
                        <button type="button" class="btn-ghost min-h-9 px-3 py-1.5 text-xs" data-set-all="yes">{{ __('Alle Ja') }}</button>
                        <button type="button" class="btn-ghost min-h-9 px-3 py-1.5 text-xs" data-set-all="no">{{ __('Alle Nein') }}</button>
                    </div>
                </div>

                <ul class="space-y-2">
                    @foreach ($poll->options as $option)
                        @php
                            $current = old('answers.'.$option->id, $me ? $results->answer($me->id, $option->id)?->value : null);
                            $c = $results->counts[$option->id];
                        @endphp
                        <li @class(['card flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:gap-4 sm:p-4', 'ring-2 ring-ink/70' => $results->isBest($option)])>
                            <div class="flex flex-1 items-center gap-3">
                                <div class="grid w-14 shrink-0 place-items-center rounded-xl bg-brand-50 py-1.5 text-ink dark:bg-brand-900/50">
                                    <span class="text-xs font-semibold uppercase">{{ DisplayTime::weekday($option->starts_at) }}</span>
                                    <span class="text-lg leading-tight font-semibold">{{ DisplayTime::local($option->starts_at)->format('j') }}</span>
                                    <span class="text-[11px] uppercase">{{ DisplayTime::local($option->starts_at)->isoFormat('MMM') }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium">{{ $option->timeLabel() }}</p>
                                    <p class="text-sm text-zinc-500">
                                        <span class="text-success-600 dark:text-success-400">✓ {{ $c['yes'] }}</span>
                                        @if ($poll->allow_maybe)<span class="ml-2 text-maybe-600 dark:text-maybe-400">~ {{ $c['maybe'] }}</span>@endif
                                        @if ($results->isBest($option))<span class="ml-2 font-medium text-ink">★ {{ __('beliebt') }}</span>@endif
                                    </p>
                                </div>
                            </div>
                            <fieldset class="flex w-full gap-1 rounded-xl bg-zinc-100 p-1 sm:w-auto sm:min-w-80 dark:bg-zinc-800">
                                <legend class="sr-only">{{ $option->fullLabel() }}</legend>
                                <label class="vote-choice yes"><input type="radio" name="answers[{{ $option->id }}]" value="yes" @checked($current === 'yes')><x-glyph name="check" class="size-4" />{{ __('Ja') }}</label>
                                @if ($poll->allow_maybe)
                                    <label class="vote-choice maybe"><input type="radio" name="answers[{{ $option->id }}]" value="maybe" @checked($current === 'maybe')><x-glyph name="tilde" class="size-4" />{{ __('Wenn nötig') }}</label>
                                @endif
                                <label class="vote-choice no"><input type="radio" name="answers[{{ $option->id }}]" value="no" @checked($current === 'no')><x-glyph name="x" class="size-4" />{{ __('Nein') }}</label>
                            </fieldset>
                        </li>
                    @endforeach
                </ul>
                <x-field-error name="answers" />

                <div class="sticky bottom-20 z-10 sm:static">
                    <button type="submit" class="btn-primary min-h-12 w-full text-base sm:w-auto sm:px-8">{{ $me ? __('Antwort aktualisieren') : __('Antwort senden') }}</button>
                </div>
                @guest
                    @if (! $me)
                        <p class="text-xs text-zinc-500">{{ __('Kein Konto nötig. Du bekommst per E-Mail einen Link, mit dem du deine Antwort ändern kannst.') }}</p>
                    @endif
                @endguest
            </form>
        </section>
    @endif

    {{-- Overview table --}}
    <section class="mt-12" id="ergebnis">
        <h2 class="text-xl font-medium">{{ __('Übersicht') }}</h2>
        @if ($participantCount === 0)
            <div class="card mt-4 p-8 text-center text-zinc-500">{{ __('Noch keine Antworten. Sei die erste Person!') }}</div>
        @else
            @include('polls.partials.results-table', ['poll' => $poll, 'results' => $results, 'me' => $me])
        @endif
    </section>
</x-layouts.app>
