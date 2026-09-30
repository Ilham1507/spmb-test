<x-guest-layout>
    @slot('auth_title', 'Buat akun peserta')
    @slot('auth_subtitle', 'Gunakan nomor WhatsApp aktif untuk login.')

    <form method="POST" action="{{ route('register') }}" class="auth-form auth-register" x-data="registrationCheck()" @submit.prevent="checkBeforeRegister($event)">
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
            <div class="auth-password-field">
                <input id="password" x-ref="password" x-init="$el.type = 'password'" type="password" class="auth-input-password" name="password" required autocomplete="new-password">
                <button type="button" @click="$refs.password.type = $refs.password.type === 'password' ? 'text' : 'password'; showPassword = $refs.password.type === 'text'" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" class="auth-password-toggle">
                    <svg x-show="!showPassword" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-cloak x-show="showPassword" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 3 18 18M10.6 6.2A11 11 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-2.1 2.9M6.2 6.2C3.4 8 2 12 2 12s3.5 6 10 6a10.8 10.8 0 0 0 4.1-.8M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>
        <div>
            <label for="password_confirmation" class="mb-1.5 block">Konfirmasi Kata Sandi</label>
            <div class="auth-password-field">
                <input id="password_confirmation" x-ref="passwordConfirmation" x-init="$el.type = 'password'" type="password" class="auth-input-password" name="password_confirmation" required autocomplete="new-password">
                <button type="button" @click="$refs.passwordConfirmation.type = $refs.passwordConfirmation.type === 'password' ? 'text' : 'password'; showConfirmation = $refs.passwordConfirmation.type === 'text'" :aria-label="showConfirmation ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" class="auth-password-toggle">
                    <svg x-show="!showConfirmation" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-cloak x-show="showConfirmation" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 3 18 18M10.6 6.2A11 11 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-2.1 2.9M6.2 6.2C3.4 8 2 12 2 12s3.5 6 10 6a10.8 10.8 0 0 0 4.1-.8M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>
        <button type="submit" class="auth-button" :disabled="checking"><span x-show="!checking">Buat Akun</span><span x-cloak x-show="checking">Memeriksa data...</span></button>
        <p x-cloak x-show="submitError" x-text="submitError" class="m-0 rounded-xl bg-rose-50 px-3 py-2 text-center text-xs font-bold leading-relaxed text-rose-700"></p>
        <p class="m-0 text-center text-xs text-slate-500">Sudah punya akun? <a href="{{ route('login') }}" class="auth-link">Masuk</a></p>

        <div x-cloak x-show="checkOpen" x-transition.opacity class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-3xl bg-white p-5 shadow-2xl sm:p-6" @click.outside="closeCheck()">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-100 text-lg font-black text-blue-700">?</div>
                <template x-if="existingAccount"><div><h2 class="mt-4 text-xl font-black text-slate-950">Akun sudah ditemukan</h2><p class="mt-2 text-sm leading-relaxed text-slate-600">Apakah ini akun kamu?</p><div class="mt-4 rounded-2xl bg-slate-50 p-4"><p class="font-black text-slate-900" x-text="existingAccount?.name"></p><p class="mt-1 text-sm font-semibold text-slate-500" x-text="existingAccount?.phone"></p></div><div class="mt-5 grid gap-2 sm:grid-cols-2"><button type="button" @click="closeCheck()" class="rounded-xl bg-slate-100 px-4 py-3 text-sm font-black text-slate-700">Bukan / ubah nomor</button><a href="{{ route('login') }}" class="rounded-xl bg-blue-700 px-4 py-3 text-center text-sm font-black text-white">Ya, masuk</a></div></div></template>
            </div>
        </div>
    </form>

    <script>
        function registrationCheck() {
            return {
                checking: false, checkOpen: false, showPassword: false, showConfirmation: false, existingAccount: null, submitError: '', form: null,
                async checkBeforeRegister(event) {
                    const form = event.target;
                    this.form = form;
                    if (!form.checkValidity()) return form.reportValidity();
                    this.submitError = '';
                    this.checking = true;
                    try {
                        const fields = new FormData(form);
                        const url = new URL(@js(route('register.check-visit')), window.location.origin);
                        url.search = new URLSearchParams({ name: fields.get('name') || '', phone: fields.get('phone') || '' });
                        const response = await fetch(url, { headers: { Accept: 'application/json' } });
                        const result = await response.json();
                        this.existingAccount = result.existing_account || null;
                        if (this.existingAccount) {
                            this.checkOpen = true;
                            form.classList.remove('is-submitting');
                            return;
                        }
                    } catch (_) { /* Layanan cek hanya membantu; pendaftaran tetap dapat dilanjutkan. */ }
                    this.submitForm();
                },
                closeCheck() { this.checkOpen = false; this.checking = false; },
                submitForm() {
                    this.checkOpen = false;
                    this.checking = true;
                    window.showGlobalLoading?.('Membuat akun', 'Mohon tunggu sebentar.');
                    const form = this.form || document.querySelector('form.auth-register');
                    if (!(form instanceof HTMLFormElement)) {
                        this.checking = false;
                        this.submitError = 'Formulir tidak ditemukan. Muat ulang halaman lalu coba lagi.';
                        window.hideGlobalLoading?.();
                        return;
                    }
                    // Gunakan method asli form agar tidak memicu ulang @submit.prevent Alpine.
                    HTMLFormElement.prototype.submit.call(form);
                },
            };
        }
    </script>
</x-guest-layout>
