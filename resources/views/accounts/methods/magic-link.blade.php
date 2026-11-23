<section class="card p-5 sm:p-6">
    <h2 class="flex items-center gap-2 font-medium"><x-glyph name="mail" class="size-5 text-ink" />{{ __('Per E-Mail-Link') }}</h2>
    <form method="POST" action="{{ route('magic-link.request') }}" class="mt-4 space-y-3">
        @csrf
        <div>
            <label for="ml-email" class="label">{{ __('E-Mail') }}</label>
            <input id="ml-email" name="email" type="email" maxlength="255" required autocomplete="email webauthn" value="{{ old('email') }}"
                   class="input @error('email') input-error @enderror" placeholder="erika@example.com">
            <x-field-error name="email" />
        </div>
        <button type="submit" class="btn-primary w-full">{{ __('Anmeldelink senden') }}</button>
    </form>
</section>
