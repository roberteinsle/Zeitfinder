{{-- Rows of date + optional time window. $name: input name, $rows: old values, $minOne: require one row --}}
@php $rows = $rows ?? []; @endphp
<div data-option-rows="{{ $name }}" @if ($minOne ?? false) data-min-one @endif>
    <div class="hidden grid-cols-[1fr_7rem_7rem_2.75rem] gap-2 px-1 pb-2 text-xs font-medium tracking-wide text-zinc-500 uppercase sm:grid">
        <span>{{ __('Datum') }}</span><span>{{ __('Von') }}</span><span>{{ __('Bis') }}</span><span></span>
    </div>

    <div data-option-list class="space-y-3 sm:space-y-2">
        @foreach ($rows as $i => $row)
            @include('polls.partials.option-row', ['name' => $name, 'index' => $i, 'row' => is_array($row) ? $row : []])
        @endforeach
    </div>

    <template data-option-template>
        @include('polls.partials.option-row', ['name' => $name, 'index' => null, 'row' => []])
    </template>

    <div class="mt-4 flex flex-wrap gap-2">
        <button type="button" class="btn-secondary" data-add-option="next-day"><x-glyph name="plus" class="size-4" />{{ __('Weiterer Tag') }}</button>
        <button type="button" class="btn-ghost" data-add-option="same-day"><x-glyph name="plus" class="size-4" />{{ __('Weitere Uhrzeit am selben Tag') }}</button>
    </div>
    <p class="mt-3 text-xs text-zinc-500">{{ __('Ohne Uhrzeit gilt der Termin als ganztägig. Alle Zeiten in deutscher Zeit (Europe/Berlin).') }}</p>
</div>
