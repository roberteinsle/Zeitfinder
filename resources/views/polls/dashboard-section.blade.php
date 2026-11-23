@php use App\Support\DisplayTime; @endphp
<section>
    <div class="flex items-center justify-between gap-4">
        <h2 class="text-xl font-semibold">{{ __('Meine Umfragen') }}</h2>
        <a href="{{ route('polls.create') }}" class="btn-primary"><x-glyph name="plus" class="size-4" />{{ __('Neue Umfrage') }}</a>
    </div>

    @if ($ownPolls->isEmpty())
        <div class="card mt-4 p-8 text-center">
            <p class="text-zinc-500">{{ __('Du hast noch keine Umfrage erstellt.') }}</p>
        </div>
    @else
        <ul class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach ($ownPolls as $poll)
                <li>
                    <a href="{{ route('polls.show', $poll) }}" class="card block p-4 transition hover:border-brand-300 hover:shadow-md dark:hover:border-brand-700">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-semibold break-words">{{ $poll->title }}</p>
                            <span @class([
                                'shrink-0 rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' => $poll->isFinalized(),
                                'bg-brand-50 text-brand-700 dark:bg-brand-900/60 dark:text-brand-200' => $poll->isOpen(),
                            ])>{{ $poll->status->label() }}</span>
                        </div>
                        <p class="mt-1 text-sm text-zinc-500">{{ trans_choice(':count Teilnahme|:count Teilnahmen', $poll->participants_count, ['count' => $poll->participants_count]) }} · {{ __('erstellt :date', ['date' => DisplayTime::date($poll->created_at ?? now())]) }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($participations->isNotEmpty())
        <h2 class="mt-10 text-xl font-semibold">{{ __('Hier habe ich abgestimmt') }}</h2>
        <ul class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach ($participations as $participation)
                <li>
                    <a href="{{ route('polls.show', $participation->poll) }}" class="card block p-4 transition hover:border-brand-300 hover:shadow-md dark:hover:border-brand-700">
                        <p class="font-semibold break-words">{{ $participation->poll->title }}</p>
                        <p class="mt-1 text-sm text-zinc-500">{{ __('von :name', ['name' => $participation->poll->organizer_name]) }} · {{ $participation->poll->status->label() }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</section>
