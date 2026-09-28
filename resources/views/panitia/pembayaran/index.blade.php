@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.panitia')

@section('title', 'Approve Pembayaran')
@section('page_title', 'Approve Pembayaran')

@section('content')
    @php($canRecordPayment = auth()->user()?->hasRole('panitia') || auth()->user()?->hasRole('admin'))
    <div class="payment-review mx-auto max-w-[1360px]">
        @if($canRecordPayment)
        <section class="mb-6 rounded-3xl border border-violet-100 bg-white p-5 shadow-sm" x-data="paymentEntry({ candidates: @js($visits->map(fn ($visit) => ['key' => 'visit:'.$visit->id, 'name' => $visit->full_name, 'phone' => $visit->visitor_phone, 'source' => 'Kunjungan siswa'])->concat($applicants->map(fn ($applicant) => ['key' => 'applicant:'.$applicant->id, 'name' => $applicant->biodata?->full_name ?? $applicant->user?->name, 'phone' => $applicant->user?->phone, 'source' => 'Pendaftar']))->unique(fn ($candidate) => preg_replace('/\D+/', '', (string) $candidate['phone']))->values()), formAmount: @js($formFeeAmount) })">
            <div class="mb-4"><p class="text-xs font-black uppercase tracking-[.14em] text-violet-700">Meja Panitia</p><h2 class="mt-1 text-xl font-black text-slate-950">Input & setujui pembayaran</h2><p class="mt-1 text-sm text-slate-500">Pilih calon siswa dari buku kunjungan atau pendaftar. Nama dan WhatsApp akan terisi otomatis dan tidak dapat diubah. Akun dari kunjungan dibuat otomatis saat pembayaran formulir dicatat.</p></div>
            <form method="POST" action="{{ route(request()->routeIs('admin.*') ? 'admin.pembayaran.store' : 'panitia.pembayaran.store') }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2">@csrf
                <div><label class="admin-label">Pilih calon siswa</label><select name="candidate" x-model="selectedKey" required class="admin-input"><option value="">— Pilih dari kunjungan / pendaftar —</option>@if($visits->isNotEmpty())<optgroup label="Dari kunjungan siswa">@foreach($visits as $visit)<option value="visit:{{ $visit->id }}">{{ $visit->full_name }} · {{ $visit->visitor_phone }}</option>@endforeach</optgroup>@endif @if($applicants->isNotEmpty())<optgroup label="Pendaftar yang sudah memiliki akun">@foreach($applicants as $applicant)<option value="applicant:{{ $applicant->id }}">{{ $applicant->biodata?->full_name ?? $applicant->user?->name }} · {{ $applicant->user?->phone }}</option>@endforeach</optgroup>@endif</select><p x-show="candidates.length === 0" class="mt-1 text-xs font-semibold text-amber-700">Belum ada calon siswa. Catat dulu melalui menu Kunjungan Siswa.</p></div>
                <div><label class="admin-label">Jenis pembayaran</label><select name="fee_type" x-model="feeType" required class="admin-input"><option value="formulir">Biaya formulir</option><option value="daftar_ulang">Daftar ulang (DU)</option></select></div>
                <div><label class="admin-label">Nama siswa</label><input :value="selected?.name || ''" readonly class="admin-input bg-slate-50 text-slate-500" placeholder="Muncul setelah calon siswa dipilih"></div>
                <div><label class="admin-label">Nomor WhatsApp siswa</label><input :value="selected?.phone || ''" readonly class="admin-input bg-slate-50 text-slate-500" placeholder="Muncul setelah calon siswa dipilih"></div>
                <div><label class="admin-label" x-text="feeType === 'formulir' ? 'Nominal formulir (ditetapkan keuangan)' : 'Nominal DU diterima'"></label><input type="number" name="amount" min="1" :value="feeType === 'formulir' ? formAmount : '{{ old('amount') }}'" :readonly="feeType === 'formulir'" :required="feeType === 'daftar_ulang'" class="admin-input" :class="feeType === 'formulir' && 'bg-slate-50 text-slate-500'" :placeholder="feeType === 'formulir' ? 'Otomatis sesuai pengaturan biaya formulir' : 'Masukkan nominal DU'"><p x-show="feeType === 'formulir'" class="mt-1 text-xs font-semibold text-violet-700">Nominal ditentukan bendahara/admin dan tidak dapat diubah panitia.</p></div>
                <div><label class="admin-label">Metode</label><select name="payment_method" required class="admin-input"><option value="cash">Tunai</option><option value="transfer">Transfer</option></select></div>
                <div><label class="admin-label">Bukti pembayaran</label><input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" required class="admin-input"><p class="mt-1 text-xs text-slate-500">JPG, PNG, atau PDF; maksimal 2 MB.</p></div>
                <div><label class="admin-label">Nomor referensi / catatan</label><input name="reference_number" value="{{ old('reference_number') }}" class="admin-input" placeholder="Opsional"></div>
                <div class="md:col-span-2"><p class="rounded-xl bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-900">Untuk DU, pilih siswa yang sudah membayar formulir. Nominal dapat diisi sesuai cicilan yang diterima; bendahara memilih rincian biaya saat menerima setiap pembayaran, lalu invoice BMT diterbitkan.</p></div>
                <div class="md:col-span-2"><button class="rounded-xl bg-violet-700 px-5 py-3 text-sm font-black text-white hover:bg-violet-800">Simpan & setujui pembayaran</button></div>
            </form>
        </section>
        @else
        <section class="mb-6 rounded-3xl border border-violet-100 bg-white p-5 shadow-sm"><p class="text-xs font-black uppercase tracking-[.14em] text-violet-700">Persetujuan pembayaran</p><h2 class="mt-1 text-xl font-black text-slate-950">Periksa dan setujui bukti pembayaran</h2><p class="mt-1 text-sm text-slate-500">Kepala sekolah dapat menyetujui atau menolak pembayaran yang masuk. Pencatatan pembayaran dilakukan oleh panitia.</p></section>
        @endif
        <x-payment-transaction-table :transactions="$transactions" :route-prefix="request()->routeIs('admin.*') ? 'admin.' : 'panitia.'" accent="violet" show-pagination />
    </div>
    <script>
        function paymentEntry(config) {
            return {
                candidates: config.candidates || [], formAmount: config.formAmount || 0, selectedKey: @js(old('candidate', '')), feeType: @js(old('fee_type', 'formulir')),
                get selected() { return this.candidates.find((candidate) => candidate.key === this.selectedKey) || null; },
            };
        }
    </script>
@endsection
