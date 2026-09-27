<x-guest-layout>
    @slot('auth_title', 'Buat akun peserta')
    @slot('auth_subtitle', 'Gunakan nomor WhatsApp aktif untuk login.')

    <form method="POST" action="{{ route('register') }}" class="auth-form auth-register" x-data="{ showPassword:false, showConfirmation:false }">
        @csrf
        <div class="register-identity">
            <div>
                <label for="name" class="mb-1.5 block">Nama Lengkap</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <label for="phone" class="mb-1.5 block">Nomor WhatsApp</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel" inputmode="numeric" placeholder="08xxxxxxxxxx">
                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
            </div>
        </div>
        <div>
            <label for="password" class="mb-1.5 block">Kata Sandi</label>
            <div class="auth-password-field"><input id="password" type="password" class="auth-input-password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password"><button type="button" @click="showPassword=!showPassword" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" class="auth-password-toggle"><svg x-show="!showPassword" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg x-cloak x-show="showPassword" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 3 18 18M10.6 6.2A11 11 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-2.1 2.9M6.2 6.2C3.4 8 2 12 2 12s3.5 6 10 6a10.8 10.8 0 0 0 4.1-.8M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg></button></div>
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>
        <div>
            <label for="password_confirmation" class="mb-1.5 block">Konfirmasi Kata Sandi</label>
            <div class="auth-password-field"><input id="password_confirmation" type="password" class="auth-input-password" :type="showConfirmation ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"><button type="button" @click="showConfirmation=!showConfirmation" :aria-label="showConfirmation ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" class="auth-password-toggle"><svg x-show="!showConfirmation" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg x-cloak x-show="showConfirmation" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 3 18 18M10.6 6.2A11 11 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-2.1 2.9M6.2 6.2C3.4 8 2 12 2 12s3.5 6 10 6a10.8 10.8 0 0 0 4.1-.8M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg></button></div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>
        <button type="submit" class="auth-button">Buat Akun</button>
        <p class="m-0 text-center text-xs text-slate-500">Sudah punya akun? <a href="{{ route('login') }}" class="auth-link">Masuk</a></p>
    </form>
</x-guest-layout>
