<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="label">E-Mail-Adresse</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   class="input-field {{ $errors->has('email') ? 'input-field-error' : '' }}"
                   placeholder="name@trafficdesign.de"
                   required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="label">Passwort</label>
            <input id="password"
                   type="password"
                   name="password"
                   class="input-field {{ $errors->has('password') ? 'input-field-error' : '' }}"
                   placeholder="Passwort eingeben"
                   required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        {{-- Remember + Forgot --}}
        <div class="flex items-center justify-between">
            <label for="remember_me" class="flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" class="checkbox-field" name="remember">
                <span class="text-sm text-surface-600">Angemeldet bleiben</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-brand-primary hover:text-brand-primary-hover transition-colors">
                    Passwort vergessen?
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-primary w-full justify-center">
            Anmelden
        </button>
    </form>
</x-guest-layout>
