<x-guest-layout>
    @slot('auth_title', 'Buat kata sandi baru')
    @slot('auth_subtitle', 'Kode verifikasi berlaku 5 menit setelah dikirim.')
    @slot('auth_compact', 'true')
    <form method="POST" action="{{ route('password.store') }}" class="auth-form" x-data="{ showPassword: false }">
        @csrf
        @if ($request->route('token') === 'kode')
            <div>
                <x-input-label for="phone" value="Nomor WhatsApp" />
                <x-text-input id="phone" class="mt-1 block w-full" type="tel" inputmode="tel" autocomplete="tel" name="phone" :value="old('phone', $request->phone)" placeholder="08xxxxxxxxxx" required />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="token" value="Kode verifikasi WhatsApp" />
                <x-text-input id="token" class="mt-1 block w-full" style="letter-spacing:.3em;font-size:20px!important" type="text" name="token" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="6 digit kode" required />
                <x-input-error :messages="$errors->get('token')" class="mt-2" />
            </div>
        @else
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <input type="hidden" name="phone" value="{{ old('phone', $request->phone) }}">
            <div>
            <x-input-label value="Nomor WhatsApp" />
            <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold text-slate-700">{{ $request->phone }}</div>
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
        @endif
        <div>
            <x-input-label for="password" value="Kata sandi baru" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" x-bind:type="showPassword ? 'text' : 'password'" name="password" minlength="8" placeholder="Minimal 8 karakter" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="Ulangi kata sandi baru" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" x-bind:type="showPassword ? 'text' : 'password'" name="password_confirmation" minlength="8" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        <button type="button" class="auth-link" style="text-align:right" @click="showPassword = !showPassword" x-text="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">Tampilkan kata sandi</button>
        <button type="submit" class="auth-button">Simpan kata sandi</button>
        <a href="{{ route('password.request') }}" class="auth-link" style="text-align:center">Kode kedaluwarsa? Minta kode baru</a>
    </form>
</x-guest-layout>
