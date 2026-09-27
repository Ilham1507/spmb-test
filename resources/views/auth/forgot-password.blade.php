<x-guest-layout>
    @slot('auth_title', 'Lupa kata sandi?')
    @slot('auth_subtitle', 'Masukkan nomor WhatsApp akunmu.')

    <form method="POST" action="{{ route('password.email') }}" style="display:grid;gap:14px">
        @csrf
        <div>
            <x-input-label for="phone" value="Nomor WhatsApp" />
            <x-text-input id="phone" class="mt-1 block w-full" type="tel" inputmode="numeric" name="phone" :value="old('phone')" placeholder="08xxxxxxxxxx" required autofocus />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div style="display:flex;align-items:center;gap:10px;padding:11px 13px;border:1px solid #bae8dc;border-radius:13px;background:#effcf7;color:#35685d;font-size:12px;font-weight:700;line-height:1.35">
            <span aria-hidden="true" style="display:grid;place-items:center;flex:0 0 29px;width:29px;height:29px;border-radius:50%;background:#d2f5e8;color:#087a61;font-size:15px">&#9201;</span>
            <span>Tautan reset dikirim lewat WhatsApp dan berlaku selama <strong>5 menit</strong>.</span>
        </div>

        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 py-2.5 font-bold text-white shadow-lg shadow-emerald-900/20 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl">
            Kirim Tautan Reset
        </button>

        <a href="{{ route('login') }}" style="display:flex;align-items:center;justify-content:center;gap:7px;min-height:42px;border:2px solid #b9d8d2;border-radius:12px;background:#fff;color:#08745e;font-size:13px;font-weight:900;text-decoration:none">
            <span aria-hidden="true">&larr;</span> Kembali ke Halaman Masuk
        </a>
    </form>

    @if (session('status'))
        <dialog id="reset-link-success" style="width:min(420px,calc(100% - 28px));padding:0;border:0;border-radius:24px;background:#fff;box-shadow:0 28px 80px rgba(10,52,44,.28);overflow:hidden">
            <div style="padding:27px 25px 24px;text-align:center">
                <div style="display:grid;place-items:center;width:62px;height:62px;margin:0 auto 14px;border-radius:50%;background:#d9f8eb;color:#078467;font-size:30px;font-weight:900">&#10003;</div>
                <h2 style="margin:0;color:#102f43;font-size:23px;font-weight:900">Tautan sudah dikirim!</h2>
                <p style="margin:8px auto 20px;max-width:330px;color:#5b7083;font-size:13px;font-weight:700;line-height:1.55">Periksa pesan WhatsApp pada nomor yang kamu masukkan. Tautan hanya berlaku selama 5 menit.</p>
                <div style="display:grid;gap:10px">
                    <button type="button" onclick="openResetWhatsApp()" style="display:flex;align-items:center;justify-content:center;min-height:47px;border:0;border-radius:13px;background:linear-gradient(90deg,#08a879,#079583);color:#fff;font-size:14px;font-weight:900;cursor:pointer;box-shadow:0 8px 20px rgba(7,149,131,.22)">Buka WhatsApp</button>
                    <a href="{{ route('login') }}" style="display:flex;align-items:center;justify-content:center;min-height:45px;border:2px solid #c5ded9;border-radius:13px;background:#fff;color:#08745e;font-size:13px;font-weight:900;text-decoration:none">Kembali ke Halaman Login</a>
                </div>
            </div>
        </dialog>
        <style>
            #reset-link-success::backdrop{background:rgba(8,35,31,.58);backdrop-filter:blur(4px)}
        </style>
        <script>
            function openResetWhatsApp() {
                const mobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
                window.location.href = mobile ? 'whatsapp://app' : 'https://web.whatsapp.com/';
            }
            document.addEventListener('DOMContentLoaded', () => {
                const successDialog = document.getElementById('reset-link-success');
                if (successDialog && !successDialog.open) successDialog.showModal();
            });
        </script>
    @endif

    @if ($errors->has('phone'))
        <dialog id="reset-link-error" style="width:min(420px,calc(100% - 28px));padding:0;border:0;border-radius:24px;background:#fff;box-shadow:0 28px 80px rgba(10,52,44,.28);overflow:hidden">
            <div style="padding:27px 25px 24px;text-align:center">
                <div style="display:grid;place-items:center;width:62px;height:62px;margin:0 auto 14px;border-radius:50%;background:#ffe5e8;color:#d82e4d;font-size:27px;font-weight:900">!</div>
                <h2 style="margin:0;color:#102f43;font-size:23px;font-weight:900">Nomor belum bisa diproses</h2>
                <p style="margin:8px auto 20px;max-width:340px;color:#5b7083;font-size:13px;font-weight:700;line-height:1.55">{{ $errors->first('phone') }}</p>
                <div style="display:grid;gap:10px">
                    <button type="button" onclick="document.getElementById('reset-link-error').close();document.getElementById('phone')?.focus()" style="min-height:47px;border:0;border-radius:13px;background:linear-gradient(90deg,#08a879,#079583);color:#fff;font-size:14px;font-weight:900;cursor:pointer;box-shadow:0 8px 20px rgba(7,149,131,.22)">Periksa Nomor Lagi</button>
                    <a href="{{ route('register') }}" style="display:flex;align-items:center;justify-content:center;min-height:45px;border:2px solid #c5ded9;border-radius:13px;background:#fff;color:#08745e;font-size:13px;font-weight:900;text-decoration:none">Buat Akun Baru</a>
                </div>
            </div>
        </dialog>
        <style>
            #reset-link-error::backdrop{background:rgba(8,35,31,.58);backdrop-filter:blur(4px)}
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const errorDialog = document.getElementById('reset-link-error');
                if (errorDialog && !errorDialog.open) errorDialog.showModal();
            });
        </script>
    @endif
</x-guest-layout>
