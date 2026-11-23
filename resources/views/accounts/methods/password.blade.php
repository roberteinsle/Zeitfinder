<details class="card group p-5 sm:p-6" @if ($errors->has('password')) open @endif>
    <summary class="flex cursor-pointer list-none items-center justify-between font-semibold">
        <span class="flex items-center gap-2"><x-glyph name="lock" class="size-5 text-brand-600" />{{ __('Mit Passwort und Zwei-Faktor-Code') }}</span>
        <span class="text-zinc-400 transition group-open:rotate-90">›</span>
    </summary>
    <form method="POST" action="{{ route('login.password') }}" class="mt-4 space-y-3">
        @csrf
        <div>
            <label for="pw-email" class="label">{{ __('E-Mail') }}</label>
            <input id="pw-email" name="email" type="email" maxlength="255" required autocomplete="username" value="{{ old('email') }}" class="input">
        </div>
        <div>
            <label for="pw-password" class="label">{{ __('Passwort') }}</label>
            <input id="pw-password" name="password" type="password" maxlength="255" required autocomplete="current-password" class="input @error('password') input-error @enderror">
            <x-field-error name="password" />
        </div>
        <button type="submit" class="btn-secondary w-full">{{ __('Weiter') }}</button>
        <p class="text-xs text-zinc-500">{{ __('Ein Passwort kannst du nach der ersten Anmeldung unter „Sicherheit“ einrichten.') }}</p>
    </form>
</details>
