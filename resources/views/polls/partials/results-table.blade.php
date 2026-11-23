@php
    use App\Modules\Polls\Enums\Answer;
    use App\Support\DisplayTime;
    $finalId = $poll->final_option_id;
@endphp
<div class="card mt-4 overflow-hidden">
    <div class="overflow-x-auto overscroll-x-contain">
        <table class="w-full border-separate border-spacing-0 text-sm">
            <caption class="sr-only">{{ __('Antworten aller Teilnehmenden') }}</caption>
            <thead>
                <tr>
                    <th scope="col" class="sticky left-0 z-10 min-w-28 sm:min-w-36 border-b border-zinc-200 bg-white px-4 py-3 text-left font-medium text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900">{{ __('Name') }}</th>
                    @foreach ($poll->options as $option)
                        <th scope="col" @class([
                            'min-w-20 border-b border-zinc-200 px-2 py-3 text-center font-normal dark:border-zinc-800',
                            'bg-brand-50 dark:bg-brand-950/60' => $results->isBest($option) && ! $finalId,
                            'bg-success-50 dark:bg-success-950/60' => $finalId === $option->id,
                        ])>
                            <span class="block text-xs font-semibold text-zinc-500 uppercase">{{ DisplayTime::weekday($option->starts_at) }}</span>
                            <span class="block font-semibold text-zinc-900 dark:text-zinc-100">{{ DisplayTime::date($option->starts_at) }}</span>
                            <span class="block text-xs whitespace-nowrap text-zinc-500">{{ $option->timeLabel() }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($poll->participants as $participant)
                    @php $isMe = $me && $me->id === $participant->id; @endphp
                    <tr @class(['bg-brand-50/50 dark:bg-brand-950/30' => $isMe])>
                        <th scope="row" @class(['sticky left-0 z-10 border-b border-zinc-100 px-4 py-2 text-left font-medium dark:border-zinc-800', 'bg-white dark:bg-zinc-900' => ! $isMe, 'bg-brand-50 dark:bg-brand-950' => $isMe])>
                            <span class="flex items-center gap-2">
                                <span class="hidden size-7 shrink-0 place-items-center rounded-full sm:grid bg-zinc-100 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $participant->initials() }}</span>
                                <span class="max-w-24 truncate sm:max-w-40">{{ $participant->name }}</span>
                                @if ($isMe)<span class="text-xs font-normal text-ink">({{ __('du') }})</span>@endif
                            </span>
                        </th>
                        @foreach ($poll->options as $option)
                            @php $answer = $results->answer($participant->id, $option->id); @endphp
                            <td class="border-b border-zinc-100 p-1 text-center dark:border-zinc-800">
                                @if ($answer === Answer::Yes)
                                    <span class="cell-yes grid h-9 place-items-center rounded-lg" title="{{ __('Ja') }}"><x-glyph name="check" class="size-5" /><span class="sr-only">{{ __('Ja') }}</span></span>
                                @elseif ($answer === Answer::Maybe)
                                    <span class="cell-maybe grid h-9 place-items-center rounded-lg" title="{{ __('Wenn nötig') }}"><x-glyph name="tilde" class="size-5" /><span class="sr-only">{{ __('Wenn nötig') }}</span></span>
                                @else
                                    <span class="cell-no grid h-9 place-items-center rounded-lg" title="{{ __('Nein') }}"><x-glyph name="x" class="size-4" /><span class="sr-only">{{ __('Nein') }}</span></span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th scope="row" class="sticky left-0 z-10 bg-zinc-50 px-4 py-3 text-left font-semibold dark:bg-zinc-950">{{ __('Summe') }}</th>
                    @foreach ($poll->options as $option)
                        @php $c = $results->counts[$option->id]; @endphp
                        <td @class(['bg-zinc-50 px-2 py-3 text-center dark:bg-zinc-950', 'font-semibold text-ink' => $results->isBest($option)])>
                            <span class="block text-success-600 dark:text-success-400">✓ {{ $c['yes'] }}</span>
                            @if ($poll->allow_maybe)<span class="block text-xs text-maybe-600 dark:text-maybe-400">~ {{ $c['maybe'] }}</span>@endif
                            @if ($results->isBest($option))<span class="block text-xs">★</span>@endif
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        </table>
    </div>
</div>
