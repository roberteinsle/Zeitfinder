@php $prefix = $index === null ? null : $name.'['.$index.']'; @endphp
@php $dot = $index === null ? null : $name.'.'.$index; @endphp
<div data-option-row class="rounded-xl border border-zinc-200 bg-zinc-50/60 p-3 sm:grid sm:grid-cols-[1fr_7rem_7rem_2.75rem] sm:items-start sm:gap-2 sm:border-0 sm:bg-transparent sm:p-0 dark:border-zinc-800 dark:bg-zinc-900/40 sm:dark:bg-transparent">
    <label class="block">
        <span class="label sm:sr-only">{{ __('Datum') }}</span>
        <input type="date" data-field="date" @if ($prefix) name="{{ $prefix }}[date]" @endif value="{{ $row['date'] ?? '' }}" required
               class="input @if ($dot && $errors->has($dot.'.date')) input-error @endif">
    </label>
    <div class="mt-2 grid grid-cols-[1fr_1fr_2.75rem] items-end gap-2 sm:contents">
        <label class="block">
            <span class="label sm:sr-only">{{ __('Von') }}</span>
            <input type="time" data-field="start" @if ($prefix) name="{{ $prefix }}[start]" @endif value="{{ $row['start'] ?? '' }}" step="300"
                   class="input @if ($dot && $errors->has($dot.'.start')) input-error @endif">
        </label>
        <label class="block">
            <span class="label sm:sr-only">{{ __('Bis') }}</span>
            <input type="time" data-field="end" @if ($prefix) name="{{ $prefix }}[end]" @endif value="{{ $row['end'] ?? '' }}" step="300"
                   class="input @if ($dot && $errors->has($dot.'.end')) input-error @endif">
        </label>
        <button type="button" data-remove-option class="btn-ghost size-11 p-0" title="{{ __('Termin entfernen') }}">
            <x-glyph name="trash" /><span class="sr-only">{{ __('Termin entfernen') }}</span>
        </button>
    </div>
    @if ($dot)
        @foreach (['date', 'start', 'end'] as $f)
            <x-field-error :name="$dot.'.'.$f" />
        @endforeach
    @endif
</div>
