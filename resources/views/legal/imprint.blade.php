<x-layouts.app :title="__('Impressum')">
    <article class="card mx-auto max-w-2xl space-y-4 p-6 leading-relaxed text-zinc-700 sm:p-8 dark:text-zinc-300">
        <h1 class="text-2xl font-medium text-zinc-900 dark:text-white">{{ __('Impressum') }}</h1>
        @include('legal.operator')
        @if ($legal->imprint_extra)
            <p class="break-words whitespace-pre-line">{{ $legal->imprint_extra }}</p>
        @endif
    </article>
</x-layouts.app>
