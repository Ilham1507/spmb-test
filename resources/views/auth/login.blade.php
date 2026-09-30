<x-guest-layout>
    @php($hasActiveEvent = \App\Support\PromotionEvent::activeAnnouncements() !== [])
    @slot('auth_title', 'Login')
    @slot('auth_subtitle', 'Gunakan nomor WhatsApp yang terdaftar.')

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="auth-form" data-loading-title="Sedang menghubungkan" data-loading-message="Memeriksa akun, tunggu sebentar." x-data="{ showPassword:false, submitting:false }" @submit="submitting=true">
        @csrf
        <div>
            <label for="login" class="mb-1.5 block">Nomor WhatsApp</label>
            <input id="login" type="text" name="login" value="{{ old('login') }}" required @unless($hasActiveEvent) autofocus @endunless autocomplete="username" placeholder="08xxxxxxxxxx">
            <x-input-error :messages="$errors->get('login')" class="mt-1" />
        </div>
        <div>
            <div class="auth-password-row"><label for="password">Kata Sandi</label><a href="{{ route('password.request') }}" class="auth-link">Lupa sandi?</a></div>
            <div class="auth-password-field">
                <input id="password" type="password" class="auth-input-password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password">
                <button type="button" @click="showPassword=!showPassword" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'" class="auth-password-toggle"><svg x-show="!showPassword" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg x-cloak x-show="showPassword" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 3 18 18M10.6 6.2A11 11 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-2.1 2.9M6.2 6.2C3.4 8 2 12 2 12s3.5 6 10 6a10.8 10.8 0 0 0 4.1-.8M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg></button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>
        <label class="flex cursor-pointer items-center gap-2 !text-xs !font-semibold text-slate-500"><input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Ingat saya di perangkat ini</label>
        <button type="submit" class="auth-button" :disabled="submitting"><span x-show="!submitting">Masuk</span><span x-cloak x-show="submitting">Menghubungkan...</span></button>
        <p class="m-0 text-center text-xs text-slate-500">Belum punya akun? <a href="{{ route('register') }}" class="auth-link">Daftar</a></p>
    </form>
</x-guest-layout>
