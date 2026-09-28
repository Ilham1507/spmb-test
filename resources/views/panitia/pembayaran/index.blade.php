@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.panitia')

@section('title', 'Approve Pembayaran')
@section('page_title', 'Approve Pembayaran')

@section('content')
    <div class="payment-review mx-auto max-w-[1360px]">
        <section class="mb-6 rounded-3xl border border-violet-100 bg-white p-5 shadow-sm">
            <div class="mb-4"><p class="text-xs font-black uppercase tracking-[.14em] text-violet-700">Meja Panitia</p><h2 class="mt-1 text-xl font-black text-slate-950">Input & setujui pembayaran</h2><p class="mt-1 text-sm text-slate-500">Bukti wajib diunggah. Jika siswa belum memiliki akun, isi nama dan nomor WhatsApp; akun serta tautan aktivasi dibuat otomatis.</p></div>
            <form method="POST" action="{{ route(request()->routeIs('admin.*') ? 'admin.pembayaran.store' : 'panitia.pembayaran.store') }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2">@csrf
                <div><label class="admin-label">Siswa yang sudah ada</label><select name="applicant_id" class="admin-input"><option value="">— Buat/cari memakai data baru di bawah —</option>@foreach($applicants as $applicant)<option value="{{ $applicant->id }}">{{ $applicant->biodata?->full_name ?? $applicant->user?->name }} · {{ $applicant->user?->phone }}</option>@endforeach</select></div>
                <div><label class="admin-label">Jenis pembayaran</label><select name="fee_type" required class="admin-input"><option value="formulir">Biaya formulir</option><option value="daftar_ulang">Daftar ulang (DU)</option></select></div>
                <div><label class="admin-label">Nama siswa baru</label><input name="full_name" value="{{ old('full_name') }}" class="admin-input" placeholder="Wajib bila siswa belum ada"></div>
                <div><label class="admin-label">Nomor WhatsApp siswa baru</label><input name="phone" value="{{ old('phone') }}" class="admin-input" placeholder="08xxxxxxxxxx"></div>
                <div><label class="admin-label">Nominal sesuai tagihan</label><input type="number" name="amount" min="1" required value="{{ old('amount') }}" class="admin-input"></div>
                <div><label class="admin-label">Metode</label><select name="payment_method" required class="admin-input"><option value="cash">Tunai</option><option value="transfer">Transfer</option></select></div>
                <div><label class="admin-label">Bukti pembayaran</label><input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" required class="admin-input"><p class="mt-1 text-xs text-slate-500">JPG, PNG, atau PDF; maksimal 2 MB.</p></div>
                <div><label class="admin-label">Nomor referensi / catatan</label><input name="reference_number" value="{{ old('reference_number') }}" class="admin-input" placeholder="Opsional"></div>
                <div class="md:col-span-2"><p class="rounded-xl bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-900">Untuk DU, pilih siswa yang sudah membayar formulir. DU SPMB dicatat satu kali penuh; rincian biaya dicek bendahara sebelum diterima dan invoice BMT diterbitkan.</p></div>
                <div class="md:col-span-2"><button class="rounded-xl bg-violet-700 px-5 py-3 text-sm font-black text-white hover:bg-violet-800">Simpan & setujui pembayaran</button></div>
            </form>
        </section>
        <x-payment-transaction-table :transactions="$transactions" :route-prefix="request()->routeIs('admin.*') ? 'admin.' : 'panitia.'" accent="violet" show-pagination />
    </div>
@endsection
