<x-guest-layout>
    <div class="mb-5">
        <h1 class="text-2xl font-black text-slate-950">Buat kata sandi baru</h1>
        <p class="mt-2 text-sm text-slate-500">Gunakan kata sandi baru yang mudah diingat tetapi tidak mudah ditebak. Tautan ini kedaluwarsa 5 menit setelah dikirim.</p>
    </div>
    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <input type="hidden" name="phone" value="{{ old('phone', $request->phone) }}">
        <div>
            <x-input-label value="Nomor WhatsApp" />
            <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold text-slate-700">{{ $request->phone }}</div>
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password" value="Kata sandi baru" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="Ulangi kata sandi baru" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>
        <x-primary-button class="w-full justify-center">Simpan kata sandi baru</x-primary-button>
    </form>
</x-guest-layout>
