<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="label">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                   class="input-field" placeholder="Vor- und Nachname"
                   required autofocus autocomplete="name">
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <label for="email" class="label">E-Mail-Adresse</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="input-field" placeholder="name@trafficdesign.de"
                   required autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <label for="password" class="label">Passwort</label>
            <input id="password" type="password" name="password"
                   class="input-field" placeholder="Mindestens 8 Zeichen"
                   required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div>
            <label for="password_confirmation" class="label">Passwort bestätigen</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="input-field" placeholder="Passwort wiederholen"
                   required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('login') }}" class="text-sm text-brand-primary hover:text-brand-primary-hover transition-colors">
                Bereits registriert?
            </a>
            <button type="submit" class="btn-primary">Registrieren</button>
        </div>
    </form>
</x-guest-layout>
