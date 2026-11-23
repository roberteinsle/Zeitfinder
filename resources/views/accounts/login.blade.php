<x-layouts.app :title="__('Anmelden')">
    <div class="mx-auto max-w-md">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('Anmelden') }}</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ __('Optional – mit Konto findest du deine Umfragen jederzeit wieder. Neu hier? Die erste Anmeldung legt dein Konto an.') }}</p>

        <x-flash />

        <div class="mt-8 space-y-4">
            @foreach ($methods as $method)
                @include($method->view)
            @endforeach
        </div>
    </div>
</x-layouts.app>
