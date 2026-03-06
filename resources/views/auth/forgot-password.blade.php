<x-guest-layout>
    <div class="mb-4 text-sm text-surface-500">
        Passwort vergessen? Gib deine E-Mail-Adresse ein und wir senden dir einen Link zum Zurücksetzen.
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="label">E-Mail-Adresse</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="input-field" placeholder="name@trafficdesign.de"
                   required autofocus>
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <button type="submit" class="btn-primary w-full justify-center">
            Link zum Zurücksetzen senden
        </button>

        <div class="text-center">
            <a href="{{ route('login') }}" class="text-sm text-brand-primary hover:text-brand-primary-hover transition-colors">
                Zurück zur Anmeldung
            </a>
        </div>
    </form>
</x-guest-layout>
