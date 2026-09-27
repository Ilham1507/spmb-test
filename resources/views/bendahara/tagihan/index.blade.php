@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.bendahara')

@section('title', 'Tagihan Peserta')
@section('page_title', 'Manajemen Tagihan Pendaftaran')

@section('content')
@php
    $routePrefix = request()->routeIs('admin.*') ? 'admin.' : 'bendahara.';
    $paymentInputBills = $inputBills->mapWithKeys(function ($bill) {
        $quote = \App\Support\PaymentQuote::forBill($bill);
        $typeName = strtolower((string) $bill->jenisTagihan?->name);
        $isRegistrationFee = str_contains($typeName, 'formulir') || str_contains($typeName, 'pendaftaran');
        $isReRegistrationFee = str_contains($typeName, 'daftar ulang') || str_contains($typeName, 'du');
        $remaining = $quote['requires_selection'] ? (float) collect($quote['items'])->sum('amount') : (float) $quote['amount'];
        return [(string) $bill->id => [
            'id' => $bill->id,
            'student' => $bill->pendaftar?->biodata?->full_name ?? $bill->pendaftar?->user?->name ?? 'Peserta',
            'type' => $bill->jenisTagihan?->name ?? 'Daftar ulang',
            'wave' => $bill->pendaftar?->gelombangPendaftaran?->name ?? 'Gelombang belum ditentukan',
            'items' => $quote['items'],
            'remaining' => (int) $remaining,
            'is_registration_fee' => $isRegistrationFee,
            'is_re_registration_fee' => $isReRegistrationFee,
        ]];
    });
@endphp
<div x-data="{ inputOpen: false, selectedBill: null, selectedItems: [], amount: '', search: '', bills: @js($paymentInputBills), openInput(bill = null) { this.selectedBill = bill; this.selectedItems = []; this.amount = bill?.is_registration_fee ? bill.remaining : ''; this.search = ''; this.inputOpen = true }, selectBill(bill) { this.selectedBill = bill; this.selectedItems = []; this.amount = bill.is_registration_fee ? bill.remaining : '' }, filteredBills() { const query = this.search.toLowerCase(); return Object.values(this.bills).filter(bill => (bill.student + ' ' + bill.type + ' ' + bill.wave).toLowerCase().includes(query)) }, selectedTotal() { return (this.selectedBill?.items || []).filter(item => this.selectedItems.includes(item.name)).reduce((sum, item) => sum + Number(item.amount), 0) } }">
<div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 p-5">
        <div>
            <h3 class="text-lg font-black text-slate-900">Tagihan siswa</h3>
            <p class="mt-1 text-sm text-slate-500">Lihat biaya yang sudah dibayar, sisa tagihan, dan pembayaran terakhir.</p>
        </div>
        <button type="button" @click="openInput()" class="rounded-xl bg-sky-700 px-4 py-2.5 text-sm font-black text-white shadow-sm hover:bg-sky-800">+ Input Pembayaran</button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                    <th class="p-4 pl-6">Nama</th>
                    <th class="p-4">Jenis Tagihan</th>
                    <th class="p-4">Jumlah</th>
                    <th class="p-4">Status</th>
                    <th class="p-4 pr-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($bills as $bill)
                    @php
                        $pendingAmount = $bill->transaksi
                            ->where('status', 'pending')
                            ->sum('amount');
                        $hasPendingPayment = $pendingAmount > 0;
                        $isPaid = $bill->status === 'paid' || (float) $bill->remaining_amount <= 0;
                        $hasPartialPayment = $bill->status === 'partial' || ((float) $bill->paid_amount > 0 && ! $isPaid);
                        $billTypeName = strtolower((string) $bill->jenisTagihan?->name);
                        $isDaftarUlang = str_contains($billTypeName, 'daftar ulang') || str_contains($billTypeName, 'du');

                        if ($isPaid) {
                            $statusLabel = $isDaftarUlang ? 'DU Lunas' : 'Lunas';
                            $statusClass = 'bg-emerald-100 text-emerald-700';
                            $statusHint = 'Pembayaran sudah diterima.';
                        } elseif ($hasPendingPayment) {
                            $statusLabel = 'Belum Diverifikasi';
                            $statusClass = 'bg-amber-100 text-amber-700';
                            $statusHint = 'Ada bukti pembayaran menunggu dicek.';
                        } elseif ($hasPartialPayment) {
                            $statusLabel = $isDaftarUlang ? 'Cicil DU' : 'Cicil / Sebagian';
                            $statusClass = 'bg-sky-100 text-sky-700';
                            $statusHint = 'Sebagian sudah dibayar, masih ada sisa.';
                        } else {
                            $statusLabel = $isDaftarUlang ? 'Belum Bayar DU' : 'Belum Bayar';
                            $statusClass = 'bg-rose-100 text-rose-700';
                            $statusHint = 'Belum ada konfirmasi pembayaran.';
                        }
                    @endphp
                    <tr class="hover:bg-amber-50/40">
                        <td class="p-4 pl-6">
                            <p class="font-bold text-slate-900">{{ $bill->pendaftar?->biodata?->full_name ?? $bill->pendaftar?->user?->name ?? 'Peserta' }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $bill->pendaftar?->registration_number ?? 'Belum ada nomor' }}</p>
                        </td>
                        <td class="p-4">
                            {{ $bill->jenisTagihan?->name ?? 'Tagihan SPMB' }}
                        </td>
                        <td class="p-4 font-semibold text-slate-900">Rp {{ number_format($bill->total_amount, 0, ',', '.') }}</td>
                        <td class="p-4">
                            <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $statusClass }}">{{ $statusLabel }}</span>
                            <p class="mt-1 text-xs text-slate-400">{{ $statusHint }}</p>
                        </td>
                        <td class="p-4 pr-6 text-right">
                            <a href="{{ route($routePrefix.'tagihan.invoice', $bill) }}" target="_blank" rel="noopener" class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-700 hover:bg-slate-200">Cetak BTM</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-sm font-semibold text-slate-400">Belum ada tagihan peserta.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($bills->hasPages())
        <x-per-page-pagination :paginator="$bills" />
    @endif
</div>

<div x-cloak x-show="inputOpen" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/45 p-4" @keydown.escape.window="inputOpen=false">
    <section @click.outside="inputOpen=false" class="w-full max-w-lg rounded-3xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><h3 class="font-black text-slate-950">Input pembayaran</h3><button type="button" @click="inputOpen=false" class="rounded-lg px-3 py-2 text-sm font-bold text-slate-500">Tutup</button></div>
        <form method="POST" action="{{ route($routePrefix.'pembayaran.store') }}" class="space-y-4 p-5">@csrf
            <div><label class="admin-label">Cari siswa atau tagihan</label><input type="search" x-model="search" placeholder="Contoh: Sultan atau Daftar Ulang" class="admin-input"><div class="mt-2 max-h-40 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200"><template x-for="bill in filteredBills()" :key="bill.id"><button type="button" @click="selectBill(bill)" class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left hover:bg-sky-50"><span><strong class="block text-sm" x-text="bill.student"></strong><small class="block text-slate-500" x-text="bill.type"></small><small class="text-slate-400" x-text="bill.wave"></small></span><span class="text-xs font-bold text-sky-700">Pilih</span></button></template><p x-show="filteredBills().length === 0" class="p-3 text-sm text-slate-500">Siswa atau tagihan tidak ditemukan.</p></div></div>
            <template x-if="selectedBill"><div class="space-y-4">
            <input type="hidden" name="bill_id" :value="selectedBill?.id">
            <div class="rounded-xl bg-slate-50 px-3 py-2 text-sm"><strong x-text="selectedBill.student"></strong><span class="text-slate-500" x-text="' · ' + selectedBill.type"></span><small class="mt-1 block text-slate-400" x-text="selectedBill.wave"></small></div>
            <template x-if="selectedBill.is_re_registration_fee"><div><p class="mb-2 text-xs font-semibold text-slate-500">Rincian sesuai <span x-text="selectedBill.wave"></span></p><div class="overflow-hidden rounded-2xl border border-slate-200"><template x-for="item in selectedBill.items" :key="item.name"><label class="flex cursor-pointer items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 last:border-0"><span class="flex items-center gap-3"><input type="checkbox" x-model="selectedItems" :value="item.name" class="h-4 w-4 rounded border-slate-300 text-sky-700"><span class="text-sm font-semibold" x-text="item.name"></span></span><strong class="text-sm" x-text="'Rp ' + Number(item.amount).toLocaleString('id-ID')"></strong></label></template></div><template x-for="item in selectedItems" :key="item"><input type="hidden" name="selected_items[]" :value="item"></template></div></template>
            <div><label class="admin-label">Nominal</label><input type="number" name="amount" min="1" :max="selectedBill.is_re_registration_fee ? selectedTotal() : selectedBill.remaining" x-model="amount" required :readonly="selectedBill.is_registration_fee" placeholder="Nominal diterima" class="admin-input"><p x-show="selectedBill.is_re_registration_fee" class="mt-1 text-xs text-slate-500">Dipilih: <strong x-text="'Rp ' + selectedTotal().toLocaleString('id-ID')"></strong></p></div>
            <div><label class="admin-label">Metode</label><select name="payment_method" class="admin-input"><option value="cash">Tunai</option><option value="transfer">Transfer</option></select></div>
            <div class="grid grid-cols-2 gap-3"><button type="button" @click="inputOpen=false" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600">Batal</button><button class="rounded-xl bg-sky-700 px-4 py-3 text-sm font-black text-white">Simpan</button></div>
            </div></template>
        </form>
    </section>
</div>
</div>
@endsection
