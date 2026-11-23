@php use App\Support\DisplayTime; @endphp
<x-layouts.app :title="__('Sicherheit')">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('account') }}" class="text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">← {{ __('Konto') }}</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ __('Sicherheit') }}</h1>

        <div class="mt-6"><x-flash /></div>

        {{-- Profile --}}
        <section class="card p-5 sm:p-6">
            <h2 class="font-semibold">{{ __('Profil') }}</h2>
            <form method="POST" action="{{ route('account.profile') }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf @method('PUT')
                <div class="flex-1">
                    <label for="name" class="label">{{ __('Name') }}</label>
                    <input id="name" name="name" type="text" maxlength="80" required value="{{ old('name', $user->name) }}" class="input @error('name') input-error @enderror">
                    <x-field-error name="name" />
                </div>
                <button class="btn-secondary">{{ __('Speichern') }}</button>
            </form>
        </section>

        {{-- Passkeys --}}
        <section class="card mt-6 p-5 sm:p-6" data-passkey-scope>
            <h2 class="flex items-center gap-2 font-semibold"><x-glyph name="key" class="size-5 text-brand-600" />{{ __('Passkeys') }}</h2>
            <p class="mt-1 text-sm text-zinc-500">{{ __('Anmelden per Face ID, Fingerabdruck, PIN oder Sicherheitsschlüssel.') }} @if ($user->is_admin){{ __('Für den Admin-Bereich ist ein Passkey Pflicht.') }}@endif</p>

            @if ($passkeys->isNotEmpty())
                <ul class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($passkeys as $passkey)
                        <li class="flex items-center justify-between gap-3 py-2.5">
                            <div>
                                <p class="font-medium">{{ $passkey->name }}</p>
                                <p class="text-sm text-zinc-500">{{ __('Hinzugefügt :date', ['date' => DisplayTime::date($passkey->created_at)]) }}@if ($passkey->last_used_at) · {{ __('zuletzt genutzt :date', ['date' => DisplayTime::date($passkey->last_used_at)]) }}@endif</p>
                            </div>
                            <form method="POST" action="{{ route('passkey.destroy', $passkey) }}" data-confirm="{{ __('Passkey entfernen?') }}">
                                @csrf @method('DELETE')
                                <button class="btn-ghost size-10 p-0 text-zinc-400 hover:text-red-600" title="{{ __('Entfernen') }}"><x-glyph name="trash" class="size-4" /><span class="sr-only">{{ __('Entfernen') }}</span></button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-4 flex flex-col gap-2 sm:flex-row" data-passkey-supported hidden>
                <input id="passkey-name" type="text" maxlength="60" placeholder="{{ __('Name, z. B. „Mein iPhone“') }}" class="input" aria-label="{{ __('Name des Passkeys') }}">
                <button type="button" class="btn-primary shrink-0" data-passkey-register="passkey-name"><x-glyph name="plus" class="size-4" />{{ __('Passkey hinzufügen') }}</button>
            </div>
            <p class="mt-4 text-sm text-zinc-500" data-passkey-unsupported hidden>{{ __('Dieser Browser unterstützt keine Passkeys.') }}</p>
            <p class="field-error" data-passkey-error hidden></p>
        </section>

        {{-- Password + TOTP --}}
        <section class="card mt-6 p-5 sm:p-6" id="passwort">
            <h2 class="flex items-center gap-2 font-semibold"><x-glyph name="lock" class="size-5 text-brand-600" />{{ __('Passwort mit Zwei-Faktor-Code') }}</h2>

            @if ($user->hasPasswordLogin())
                <p class="mt-2 text-sm text-emerald-700 dark:text-emerald-400">{{ __('Aktiv. Bei der Anmeldung mit Passwort fragen wir immer nach einem Code aus deiner Authenticator-App.') }}</p>
                <form method="POST" action="{{ route('account.password.disable') }}" class="mt-4" data-confirm="{{ __('Anmeldung mit Passwort deaktivieren?') }}">
                    @csrf @method('DELETE')
                    <button class="btn-danger">{{ __('Deaktivieren') }}</button>
                </form>
            @elseif ($pendingQr)
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Scanne den QR-Code mit deiner Authenticator-App und gib den angezeigten Code ein. Erst dann wird das Passwort aktiv.') }}</p>
                <div class="mt-4 flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                    <div class="rounded-xl bg-white p-3">{!! $pendingQr !!}</div>
                    <div class="w-full space-y-3">
                        <p class="text-xs text-zinc-500">{{ __('Oder manuell eingeben:') }} <code class="break-all">{{ $pendingSecret }}</code></p>
                        <form method="POST" action="{{ route('account.password.confirm') }}" class="space-y-3">
                            @csrf
                            <input name="code" type="text" inputmode="numeric" maxlength="10" autocomplete="one-time-code" required
                                   class="input font-mono tracking-widest @error('code') input-error @enderror" placeholder="123456" aria-label="{{ __('Code') }}">
                            <x-field-error name="code" />
                            <button class="btn-primary w-full">{{ __('Bestätigen') }}</button>
                        </form>
                        <form method="POST" action="{{ route('account.password.cancel') }}">
                            @csrf
                            <button class="btn-ghost w-full">{{ __('Abbrechen') }}</button>
                        </form>
                    </div>
                </div>
            @else
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Optional. Ein Passwort gibt es bei uns nur zusammen mit einem Zwei-Faktor-Code (TOTP).') }}</p>
                <form method="POST" action="{{ route('account.password.start') }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="text" name="username" value="{{ $user->email }}" autocomplete="username" class="hidden" readonly tabindex="-1" aria-hidden="true">
                    <div>
                        <label for="password" class="label">{{ __('Neues Passwort (mind. 12 Zeichen)') }}</label>
                        <input id="password" name="password" type="password" maxlength="255" required autocomplete="new-password" class="input @error('password') input-error @enderror">
                        <x-field-error name="password" />
                    </div>
                    <div>
                        <label for="password_confirmation" class="label">{{ __('Passwort wiederholen') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" maxlength="255" required autocomplete="new-password" class="input">
                    </div>
                    <button class="btn-secondary">{{ __('Weiter zur Zwei-Faktor-Einrichtung') }}</button>
                </form>
            @endif
        </section>
    </div>
</x-layouts.app>
