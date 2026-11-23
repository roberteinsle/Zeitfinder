<x-layouts.app :title="__('Neue Umfrage')">
    <div class="mx-auto max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('Neue Umfrage') }}</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ __('Schlage Termine vor und teile anschließend den Link.') }}</p>

        <form method="POST" action="{{ route('polls.store') }}" class="mt-8 space-y-6" novalidate>
            @csrf

            @if ($errors->any())
                <div class="alert-error" role="alert">{{ __('Bitte prüfe die markierten Felder.') }}</div>
            @endif

            <section class="card space-y-5 p-5 sm:p-6">
                <h2 class="text-lg font-semibold">{{ __('Worum geht es?') }}</h2>
                <div>
                    <label for="title" class="label">{{ __('Titel') }}</label>
                    <input id="title" name="title" type="text" maxlength="120" required autofocus value="{{ old('title') }}"
                           placeholder="{{ __('z. B. Teamessen im Dezember') }}" class="input @error('title') input-error @enderror">
                    <x-field-error name="title" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="location" class="label">{{ __('Ort') }} <span class="font-normal text-zinc-400">({{ __('optional') }})</span></label>
                        <input id="location" name="location" type="text" maxlength="200" value="{{ old('location') }}"
                               placeholder="{{ __('z. B. Online oder Musterstraße 1') }}" class="input @error('location') input-error @enderror">
                        <x-field-error name="location" />
                    </div>
                </div>
                <div>
                    <label for="description" class="label">{{ __('Beschreibung') }} <span class="font-normal text-zinc-400">({{ __('optional') }})</span></label>
                    <textarea id="description" name="description" rows="3" maxlength="2000" class="input @error('description') input-error @enderror">{{ old('description') }}</textarea>
                    <x-field-error name="description" />
                </div>
            </section>

            <section class="card p-5 sm:p-6">
                <div class="mb-4 flex items-baseline justify-between">
                    <h2 class="text-lg font-semibold">{{ __('Terminvorschläge') }}</h2>
                    <span class="text-sm text-zinc-500"><span data-option-count>0</span> {{ __('Termine') }}</span>
                </div>
                @include('polls.partials.option-rows', ['name' => 'options', 'rows' => old('options', []), 'minOne' => true])
                <x-field-error name="options" />
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h2 class="text-lg font-semibold">{{ __('Über dich') }}</h2>
                @auth
                    <div>
                        <label for="organizer_name" class="label">{{ __('Dein Name') }}</label>
                        <input id="organizer_name" name="organizer_name" type="text" maxlength="80" value="{{ old('organizer_name', auth()->user()->name) }}" class="input @error('organizer_name') input-error @enderror">
                        <x-field-error name="organizer_name" />
                        <p class="mt-1.5 text-xs text-zinc-500">{{ __('Benachrichtigungen gehen an :email.', ['email' => auth()->user()->email]) }}</p>
                    </div>
                @else
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="organizer_name" class="label">{{ __('Dein Name') }}</label>
                            <input id="organizer_name" name="organizer_name" type="text" maxlength="80" required autocomplete="name" value="{{ old('organizer_name') }}" class="input @error('organizer_name') input-error @enderror">
                            <x-field-error name="organizer_name" />
                        </div>
                        <div>
                            <label for="organizer_email" class="label">{{ __('Deine E-Mail') }}</label>
                            <input id="organizer_email" name="organizer_email" type="email" maxlength="255" required autocomplete="email" value="{{ old('organizer_email') }}" class="input @error('organizer_email') input-error @enderror">
                            <x-field-error name="organizer_email" />
                        </div>
                    </div>
                    <p class="text-xs text-zinc-500">{{ __('Du bekommst den Verwaltungslink per E-Mail. Ein Konto brauchst du nicht – mit Konto findest du deine Umfragen aber jederzeit wieder.') }}</p>
                @endauth

                <div class="space-y-3 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                    <label class="flex items-start gap-3">
                        <input type="hidden" name="allow_maybe" value="0">
                        <input type="checkbox" name="allow_maybe" value="1" class="checkbox mt-0.5" @checked(old('allow_maybe', '1') === '1')>
                        <span><span class="font-medium">{{ __('„Wenn nötig“ erlauben') }}</span><span class="block text-sm text-zinc-500">{{ __('Neben Ja und Nein gibt es eine dritte Antwort.') }}</span></span>
                    </label>
                    <label class="flex items-start gap-3">
                        <input type="hidden" name="notify_on_vote" value="0">
                        <input type="checkbox" name="notify_on_vote" value="1" class="checkbox mt-0.5" @checked(old('notify_on_vote') === '1')>
                        <span><span class="font-medium">{{ __('Bei jeder Antwort benachrichtigen') }}</span><span class="block text-sm text-zinc-500">{{ __('Du bekommst eine E-Mail, wenn jemand abstimmt.') }}</span></span>
                    </label>
                </div>
            </section>

            <div class="sticky bottom-20 z-10 sm:static">
                <button type="submit" class="btn-primary min-h-12 w-full text-base shadow-lg sm:w-auto sm:px-8">{{ __('Umfrage erstellen') }} <x-glyph name="arrow-right" class="size-4" /></button>
            </div>
        </form>
    </div>
</x-layouts.app>
