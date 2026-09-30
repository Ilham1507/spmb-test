@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.panitia')

@section('title', 'Approve Pembayaran')

@section('page_title', 'Approve Pembayaran')

@section('content')
    @php($canRecordPayment = auth()->user()?->hasRole('panitia') || auth()->user()?->hasRole('admin'))
    <div class="payment-review mx-auto max-w-[1360px]">
        @if ($canRecordPayment)

        <div class="mb-6" x-data="paymentEntry({ candidates: @js($paymentCandidates), formAmount: @js($formFeeAmount), oldCandidate: @js(old('candidate')), oldFeeType: @js(old('fee_type', 'formulir')), oldAmount: @js(old('amount')) })">
            <button type="button" @click="inputOpen=true" class="inline-flex items-center gap-2 rounded-2xl bg-violet-700 px-5 py-3 text-sm font-black text-white shadow-lg shadow-violet-200 transition hover:bg-violet-800">
                <span class="text-lg leading-none">+</span> Catat pembayaran
            </button>

            <template x-teleport="body"><div x-cloak x-show="inputOpen" x-transition.opacity class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-950/55 p-4" @keydown.escape.window="inputOpen=false">
                <div class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-[2rem] bg-white shadow-2xl" @click.outside="inputOpen=false">
                    <form method="POST" action="{{ route(request()->routeIs('admin.*') ? 'admin.pembayaran.store' : 'panitia.pembayaran.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="sticky top-0 z-10 flex items-start justify-between border-b border-slate-100 bg-white px-6 py-5">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[.2em] text-violet-600">Pembayaran masuk</p>
                                <h2 class="mt-1 text-xl font-black text-slate-900">Catat pembayaran</h2>
                                <p class="mt-1 text-sm text-slate-500">Pilih tagihan, lalu cari pendaftar yang masih memiliki sisa pembayaran.</p>
                            </div>
                            <button type="button" @click="inputOpen=false" class="rounded-xl px-3 py-2 text-sm font-bold text-slate-500 hover:bg-slate-100">Tutup</button>
                        </div>

                        <div class="space-y-5 px-6 py-6">
                            @if ($errors->any())
                                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
                            @endif

                            <div>
                                <label class="admin-label">Pembayaran baru</label>
                                <input type="hidden" name="fee_type" :value="feeType">
                                <div class="mt-2 grid grid-cols-2 gap-2 rounded-2xl bg-violet-50 p-1.5">
                                    <button type="button" @click="setFeeType('formulir')" class="rounded-xl px-3 py-3 text-sm font-black transition" :class="feeType === 'formulir' ? 'bg-violet-700 text-white shadow-sm' : 'text-violet-800 hover:bg-white'">Biaya formulir</button>
                                    <button type="button" @click="setFeeType('daftar_ulang')" class="rounded-xl px-3 py-3 text-sm font-black transition" :class="feeType === 'daftar_ulang' ? 'bg-violet-700 text-white shadow-sm' : 'text-violet-800 hover:bg-white'">Daftar ulang</button>
                                </div>
                            </div>

                            <div>
                                <label for="payment-candidate-search" class="admin-label">Cari pendaftar</label>
                                <input type="hidden" name="candidate" :value="selectedKey">
                                <input id="payment-candidate-search" type="search" x-model="search" @input="selectedKey=''" class="admin-input mt-2" placeholder="Ketik nama atau nomor WhatsApp pendaftar" autocomplete="off">
                                <p class="mt-2 text-xs font-semibold text-slate-500" x-show="!search.trim()">Ketik nama atau nomor WhatsApp untuk mencari pendaftar.</p>

                                <div x-cloak x-show="filteredCandidates.length > 0" class="mt-2 max-h-60 overflow-y-auto rounded-2xl border border-violet-100 bg-white p-1.5 shadow-lg shadow-violet-100/60">
                                    <template x-for="candidate in filteredCandidates" :key="candidate.key">
                                        <button type="button" @click="selectCandidate(candidate)" class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-violet-50">
                                            <span>
                                                <strong class="block text-sm text-slate-800" x-text="candidate.name"></strong>
                                                <small class="mt-0.5 block text-xs font-semibold text-slate-500" x-text="candidate.phone"></small>
                                            </span>
                                            <span class="text-right">
                                                <strong class="block text-xs font-black text-violet-700" x-text="candidate.status"></strong>
                                                <small class="mt-0.5 block text-xs font-bold text-slate-500" x-text="'Sisa Rp ' + formatRupiah(candidate.remaining)"></small>
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div x-cloak x-show="selected" class="rounded-2xl border border-violet-100 bg-violet-50 px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span>
                                        <strong class="block text-sm text-slate-900" x-text="selected?.name"></strong>
                                        <small class="block text-xs font-semibold text-slate-500" x-text="selected?.phone"></small>
                                    </span>
                                    <span class="text-right text-xs font-black text-violet-700" x-text="selected ? `${selected.status} · Sisa Rp ${formatRupiah(selected.remaining)}` : ''"></span>
                                </div>
                            </div>

                            <div x-cloak x-show="feeType === 'daftar_ulang' && selected" class="rounded-2xl border border-violet-100 bg-white p-4">
                                <p class="text-sm font-black text-slate-900">Rincian biaya yang dibayar</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">Pilih rincian yang dicakup oleh nominal pembayaran ini.</p>
                                <div class="mt-3 max-h-56 space-y-2 overflow-y-auto">
                                    <template x-for="item in selected?.items || []" :key="item.name">
                                        <label class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 px-3 py-2.5 text-sm">
                                            <span class="flex items-center gap-2"><input type="checkbox" x-model="selectedItems" :value="item.name"><span x-text="item.name"></span></span>
                                            <strong x-text="'Rp ' + formatRupiah(item.amount)"></strong>
                                        </label>
                                    </template>
                                </div>
                                <template x-for="item in selectedItems" :key="item"><input type="hidden" name="selected_items[]" :value="item"></template>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="admin-label" x-text="feeType === 'formulir' ? 'Nominal formulir' : 'Nominal daftar ulang'"></label>
                                    <input type="number" name="amount" min="1" :max="feeType === 'daftar_ulang' && selected ? selected.remaining : null" :value="amountValue" :readonly="feeType === 'formulir'" class="admin-input mt-2" required>
                                    <p x-show="feeType === 'formulir'" class="mt-2 text-xs font-semibold text-violet-700">Nominal ditetapkan keuangan.</p>
                                    <p x-show="feeType === 'daftar_ulang'" class="mt-2 text-xs font-semibold text-violet-700" x-text="selected ? `Sisa daftar ulang Rp ${formatRupiah(selected.remaining)}. Pembayaran boleh dicicil.` : 'Daftar ulang boleh dicicil.'"></p>
                                </div>
                                <div>
                                    <label class="admin-label">Metode</label>
                                    <input type="hidden" name="payment_method" :value="paymentMethod">
                                    <div class="mt-2 grid grid-cols-2 gap-2 rounded-2xl bg-violet-50 p-1.5">
                                        <button type="button" @click="paymentMethod='cash'" class="rounded-xl px-3 py-3 text-sm font-black transition" :class="paymentMethod === 'cash' ? 'bg-violet-700 text-white shadow-sm' : 'text-violet-800 hover:bg-white'">Tunai</button>
                                        <button type="button" @click="paymentMethod='transfer'" class="rounded-xl px-3 py-3 text-sm font-black transition" :class="paymentMethod === 'transfer' ? 'bg-violet-700 text-white shadow-sm' : 'text-violet-800 hover:bg-white'">Transfer</button>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="admin-label" x-text="paymentMethod === 'transfer' ? 'Bukti transfer' : 'Invoice / kwitansi tunai'"></label>
                                    <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" class="admin-input mt-2 px-3 py-2.5" required>
                                    <p x-show="paymentMethod === 'transfer'" class="mt-2 text-xs font-semibold text-slate-500">Unggah bukti transfer · JPG, PNG, atau PDF · maks. 2 MB</p>
                                    <p x-show="paymentMethod === 'cash'" class="mt-2 text-xs font-semibold text-violet-700">Unggah invoice atau kwitansi manual yang diberikan panitia · JPG, PNG, atau PDF · maks. 2 MB</p>
                                </div>
                                <div>
                                    <label class="admin-label">Nomor referensi / catatan <span class="font-medium text-slate-400">(opsional)</span></label>
                                    <input type="text" name="reference_number" value="{{ old('reference_number') }}" class="admin-input mt-2" placeholder="Contoh: nomor transfer">
                                </div>
                            </div>
                        </div>

                        <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-100 bg-white px-6 py-4">
                            <button type="button" @click="inputOpen=false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-600 hover:bg-slate-50">Batal</button>
                            <button type="submit" :disabled="!selected" class="rounded-xl bg-violet-700 px-5 py-2.5 text-sm font-black text-white shadow-lg shadow-violet-200 transition hover:bg-violet-800 disabled:cursor-not-allowed disabled:opacity-50">Simpan pembayaran</button>
                        </div>
                    </form>
                </div>
            </div></template>
        </div>

        @else
            <section class="mb-6 rounded-3xl border border-violet-100 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.14em] text-violet-700">Persetujuan pembayaran</p>
                <h2 class="mt-1 text-xl font-black text-slate-950">Periksa dan setujui bukti pembayaran</h2>
                <p class="mt-1 text-sm text-slate-500">Kepala sekolah dapat menyetujui atau menolak pembayaran yang masuk. Pencatatan pembayaran dilakukan oleh panitia.</p>
            </section>
        @endif

        <x-payment-transaction-table :transactions="$transactions" :route-prefix="request()->routeIs('admin.*') ? 'admin.' : 'panitia.'" accent="violet" show-pagination />
    </div>
@endsection

@push('scripts')
<script>
    function paymentEntry(config) {
        return {
            candidates: config.candidates || [],
            formAmount: Number(config.formAmount || 0),
            inputOpen: @js($errors->any()),
            selectedKey: config.oldCandidate || '',
            feeType: config.oldFeeType || 'formulir',
            paymentMethod: @js(old('payment_method', 'cash')),
            selectedItems: @js(old('selected_items', [])),
            search: '',
            oldAmount: config.oldAmount || '',
            get selected() {
                return this.candidates.find((candidate) => candidate.key === this.selectedKey && candidate.fee_type === this.feeType) || null;
            },
            get filteredCandidates() {
                const query = this.search.trim().toLowerCase();
                if (!query) return [];

                return this.candidates.filter((candidate) => candidate.fee_type === this.feeType && `${candidate.name} ${candidate.phone}`.toLowerCase().includes(query)).slice(0, 12);
            },
            get amountValue() {
                if (this.feeType === 'formulir') return this.selected ? this.selected.remaining : this.formAmount;
                return this.oldAmount;
            },
            setFeeType(type) {
                this.feeType = type;
                this.selectedKey = '';
                this.search = '';
                this.oldAmount = '';
                this.selectedItems = [];
            },
            selectCandidate(candidate) {
                this.selectedKey = candidate.key;
                this.search = `${candidate.name} · ${candidate.phone}`;
                this.selectedItems = [];
            },
            formatRupiah(value) {
                return Number(value || 0).toLocaleString('id-ID');
            },
        };
    }
</script>
@endpush
