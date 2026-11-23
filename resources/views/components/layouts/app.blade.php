<!DOCTYPE html>
<html lang="de" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#141112" media="(prefers-color-scheme: dark)">
    <meta name="robots" content="{{ $robots ?? 'noindex' }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}Zeitfinder</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col overflow-x-hidden">
    <a href="#inhalt" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-white focus:px-3 focus:py-2">{{ __('Zum Inhalt springen') }}</a>

    <header class="sticky top-0 z-30 border-b border-zinc-200/70 bg-white/80 backdrop-blur-lg dark:border-zinc-800 dark:bg-zinc-950/80">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ route('home') }}" aria-label="{{ __('Zeitfinder – Startseite') }}"><x-logo /></a>

            <nav class="hidden items-center gap-1 sm:flex" aria-label="{{ __('Hauptnavigation') }}">
                <a href="{{ route('home') }}" class="btn-ghost">{{ __('Start') }}</a>
                @auth
                    <a href="{{ route('account') }}" class="btn-ghost">{{ __('Meine Umfragen') }}</a>
                @endauth
                <a href="{{ route('polls.create') }}" class="btn-primary ml-2"><x-glyph name="plus" class="size-4" />{{ __('Neue Umfrage') }}</a>
            </nav>

            <div class="flex items-center gap-1">
                @auth
                    <a href="{{ route('account.security') }}" class="btn-ghost hidden sm:inline-flex" title="{{ __('Sicherheit') }}"><x-glyph name="cog" /><span class="sr-only">{{ __('Sicherheit') }}</span></a>
                    <a href="{{ route('account') }}" class="btn-ghost" title="{{ __('Konto') }}">
                        <span class="grid size-8 place-items-center rounded-full bg-brand-100 text-sm font-semibold text-ink dark:bg-brand-900">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="sr-only">{{ __('Konto') }}</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost">{{ __('Anmelden') }}</a>
                @endauth
            </div>
        </div>
    </header>

    <main id="inhalt" class="mx-auto w-full min-w-0 max-w-6xl flex-1 px-4 pt-6 pb-28 sm:px-6 sm:pt-10 sm:pb-16">
        {{ $slot }}
    </main>

    <footer class="hidden border-t border-zinc-200/70 py-8 text-sm text-zinc-500 sm:block dark:border-zinc-800">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6">
            <span>Zeitfinder</span>
            <a href="{{ route('privacy') }}" class="hover:text-zinc-800 dark:hover:text-zinc-200">{{ __('Datenschutz') }}</a>
        </div>
    </footer>

    {{-- Mobile: bottom tab bar with the main areas --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-lg sm:hidden dark:border-zinc-800 dark:bg-zinc-950/95" aria-label="{{ __('Hauptnavigation') }}">
        <div class="grid grid-cols-3">
            @php $tab = 'flex flex-col items-center gap-1 py-2.5 text-xs font-medium'; @endphp
            <a href="{{ route('home') }}" class="{{ $tab }} {{ request()->routeIs('home') ? 'text-ink' : 'text-zinc-500' }}"><x-glyph name="home" class="size-6" />{{ __('Start') }}</a>
            <a href="{{ route('polls.create') }}" class="{{ $tab }} {{ request()->routeIs('polls.create') ? 'text-ink' : 'text-zinc-500' }}"><x-glyph name="plus" class="size-6" />{{ __('Neu') }}</a>
            <a href="{{ auth()->check() ? route('account') : route('login') }}" class="{{ $tab }} {{ request()->routeIs('account*', 'login') ? 'text-ink' : 'text-zinc-500' }}"><x-glyph name="user" class="size-6" />{{ auth()->check() ? __('Konto') : __('Anmelden') }}</a>
        </div>
    </nav>
</body>
</html>
