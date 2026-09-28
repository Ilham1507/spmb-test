@extends('layouts.admin')
@section('title', 'Asal Sekolah')
@section('page_title', 'Master Asal Sekolah')
@section('content')
<style>
    [x-cloak] { display: none !important; }
    .school-search-overlay { position: fixed !important; inset: 0 !important; z-index: 2147483000 !important; display: flex !important; min-height: 100dvh !important; width: 100vw !important; align-items: center !important; justify-content: center !important; padding: 16px !important; background: rgba(15, 23, 42, .48); backdrop-filter: blur(4px); }
    .school-search-modal { width: min(100%, 680px); max-height: calc(100dvh - 32px); overflow: auto; border: 1px solid #dbeafe; border-radius: 24px; background: #fff; box-shadow: 0 24px 60px rgba(15, 23, 42, .26); }
</style>
<script>
function schoolFinder() {
    return {
        isOpen: false, query: '', results: [], searching: false, hasSearched: false, submitTried: false,
        selected: { npsn: '', nama: '', alamat: '', bentuk_pendidikan: '', status: 'aktif', desa_kelurahan: '', kecamatan: '', kabupaten_kota: '', provinsi: '', alamat_lengkap: '' },
        openModal() { this.isOpen = true; this.query = ''; this.results = []; this.hasSearched = false; this.submitTried = false; this.selected = { npsn: '', nama: '', alamat: '', bentuk_pendidikan: '', status: 'aktif', desa_kelurahan: '', kecamatan: '', kabupaten_kota: '', provinsi: '', alamat_lengkap: '' }; this.$nextTick(() => this.$refs.schoolQuery.focus()); },
        closeModal() { this.isOpen = false; },
        async searchSchools() {
            const query = this.query.trim();
            this.hasSearched = query.length >= 1;
            if (!this.hasSearched) { this.results = []; return; }
            this.searching = true;
            try {
                const response = await fetch(`{{ route('admin.master.sekolah.search') }}?q=${encodeURIComponent(query)}`, { headers: { 'Accept': 'application/json' } });
                this.results = response.ok ? await response.json() : [];
            } catch (_) { this.results = []; }
            finally { this.searching = false; }
        },
        selectSchool(school) { this.selected = school; this.query = school.nama; this.results = []; this.hasSearched = false; }
    }
}
</script>
<div class="space-y-4" x-data="schoolFinder()">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-lg font-black text-slate-950">Asal Sekolah</h2><p class="mt-1 text-sm text-slate-500">Referensi sekolah asal khusus jenjang SMP dan MTs.</p></div>
            <button type="button" class="btn-primary" @click="openModal()">+ Tambah Sekolah</button>
        </div>
        <form class="mt-4 flex gap-2"><x-list-search placeholder="Cari nama sekolah, NPSN, atau kecamatan" class="flex-1" /><button class="btn-secondary">Cari</button></form>
    </section>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="w-full min-w-[680px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">NPSN</th><th class="p-4">Nama Sekolah</th><th class="p-4">Alamat</th><th class="p-4">Status</th><th class="p-4">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse($sekolahs as $sekolah)
            <tr><td class="p-4 font-bold">{{ $sekolah->npsn }}</td><td class="p-4 font-bold text-slate-900">{{ $sekolah->nama }}<span class="ml-2 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-black text-blue-700">{{ strtoupper($sekolah->bentuk_pendidikan) }}</span><p class="mt-1 text-xs font-semibold text-slate-500">{{ collect([$sekolah->kecamatan ? 'Kec. '.$sekolah->kecamatan : null, $sekolah->kabupaten_kota])->filter()->join(', ') ?: '-' }}</p></td><td class="p-4 text-slate-600">{{ $sekolah->alamat ?: '-' }}</td><td class="p-4"><span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700">{{ ucfirst($sekolah->status ?: 'aktif') }}</span></td><td class="p-4"><div class="flex gap-3"><button type="button" class="text-xs font-bold text-sky-700" onclick="document.getElementById('edit-sekolah-{{ $sekolah->id }}').showModal()">Edit</button>@if(($sekolah->status ?: 'aktif') !== 'nonaktif')<form method="POST" action="{{ route('admin.master.sekolah.destroy', $sekolah) }}" onsubmit="return confirm('Nonaktifkan sekolah ini?')">@csrf @method('DELETE')<button class="text-xs font-bold text-rose-600">Hapus</button></form>@endif</div></td></tr>
            <dialog id="edit-sekolah-{{ $sekolah->id }}" class="w-[min(94vw,520px)] rounded-2xl p-0 shadow-2xl"><form method="POST" action="{{ route('admin.master.sekolah.update', $sekolah) }}" class="p-6">@csrf @method('PUT')<h3 class="text-lg font-black">Edit Asal Sekolah</h3><div class="mt-4 grid gap-3"><input name="npsn" value="{{ $sekolah->npsn }}" required maxlength="8" class="admin-input" placeholder="NPSN"><input name="nama" value="{{ $sekolah->nama }}" required class="admin-input" placeholder="Nama sekolah"><input name="alamat" value="{{ $sekolah->alamat }}" class="admin-input" placeholder="Alamat"><select name="status" class="admin-input"><option value="aktif" @selected(($sekolah->status ?: 'aktif') === 'aktif')>Aktif</option><option value="nonaktif" @selected($sekolah->status === 'nonaktif')>Nonaktif</option></select></div><div class="mt-5 flex justify-end gap-2"><button type="button" class="btn-secondary" onclick="this.closest('dialog').close()">Batal</button><button class="btn-primary">Simpan</button></div></form></dialog>
        @empty<tr><td colspan="5" class="p-8 text-center text-slate-500">Belum ada referensi sekolah SMP atau MTs.</td></tr>@endforelse
        </tbody></table></div><x-per-page-pagination :paginator="$sekolahs" />
    </section>

    <template x-teleport="body">
        <div x-cloak x-show="isOpen" x-transition.opacity class="school-search-overlay" @keydown.escape.window="closeModal()" @click.self="closeModal()">
            <section class="school-search-modal" role="dialog" aria-modal="true" aria-labelledby="school-modal-title">
                <form method="POST" action="{{ route('admin.master.sekolah.store') }}" class="p-5 sm:p-6">
                    @csrf
                    <input type="hidden" name="npsn" :value="selected.npsn">
                    <input type="hidden" name="nama" :value="selected.nama">
                    <input type="hidden" name="alamat" :value="selected.alamat">
                    <input type="hidden" name="bentuk_pendidikan" :value="selected.bentuk_pendidikan">
                    <input type="hidden" name="status" :value="selected.status || 'aktif'">
                    <input type="hidden" name="desa_kelurahan" :value="selected.desa_kelurahan">
                    <input type="hidden" name="kecamatan" :value="selected.kecamatan">
                    <input type="hidden" name="kabupaten_kota" :value="selected.kabupaten_kota">
                    <input type="hidden" name="provinsi" :value="selected.provinsi">

                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                        <div><p class="text-xs font-black uppercase tracking-[.16em] text-blue-700">Referensi resmi</p><h3 id="school-modal-title" class="mt-1 text-xl font-black text-slate-950">Tambah Asal Sekolah</h3><p class="mt-1 text-sm text-slate-500">Cari dari referensi resmi. Hasil dibatasi untuk SMP dan MTs.</p></div>
                        <button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-xl text-slate-600 hover:bg-slate-50" @click="closeModal()" aria-label="Tutup">×</button>
                    </div>

                    <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50/60 p-4">
                        <label class="text-sm font-black text-slate-800">Pencarian sekolah</label>
                        <div class="mt-2 flex items-center gap-2 rounded-xl border border-blue-200 bg-white px-3 shadow-sm" :class="searching ? 'ring-2 ring-blue-200' : ''">
                            <svg class="h-5 w-5 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input x-ref="schoolQuery" type="search" x-model="query" @input.debounce.150ms="searchSchools()" class="min-w-0 flex-1 border-0 bg-transparent py-3 text-sm font-semibold outline-none" placeholder="Ketik nama sekolah, NPSN, kecamatan, atau kabupaten">
                            <span x-show="searching" class="text-xs font-bold text-blue-700">Mencari…</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Ketik nama sekolah, NPSN, kecamatan, atau kabupaten. Sistem hanya menampilkan jenjang SMP dan MTs.</p>

                        <div x-show="hasSearched" class="mt-3 max-h-64 overflow-y-auto rounded-xl border border-blue-100 bg-white p-1">
                            <template x-for="school in results" :key="school.npsn">
                                <button type="button" @click="selectSchool(school)" class="block w-full rounded-lg px-3 py-3 text-left hover:bg-blue-50" :class="selected.npsn === school.npsn ? 'bg-blue-50 ring-1 ring-inset ring-blue-300' : ''">
                                    <span class="flex items-center justify-between gap-2"><span class="font-black text-slate-900" x-text="school.nama"></span><span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-black text-blue-700" x-text="school.bentuk_pendidikan"></span></span>
                                    <span class="mt-1 block text-xs text-slate-500"><span x-text="school.npsn"></span><span x-show="school.kecamatan"> · Kec. <span x-text="school.kecamatan"></span></span><span x-show="school.kabupaten_kota">, <span x-text="school.kabupaten_kota"></span></span></span>
                                </button>
                            </template>
                            <p x-show="!searching && results.length === 0" class="px-3 py-4 text-center text-sm text-slate-500">Tidak ada SMP atau MTs yang cocok.</p>
                        </div>
                    </div>

                    <div x-show="selected.npsn" x-transition class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="text-xs font-black uppercase tracking-[.12em] text-emerald-700">Sekolah terpilih</p>
                        <p class="mt-1 font-black text-slate-950" x-text="selected.nama"></p>
                        <p class="mt-1 text-sm text-slate-600"><span x-text="selected.npsn"></span> · <span x-text="selected.bentuk_pendidikan"></span></p>
                        <p class="mt-1 text-sm text-slate-500" x-text="selected.alamat_lengkap || selected.alamat || 'Alamat belum tersedia'"></p>
                    </div>

                    <p x-show="submitTried && !selected.npsn" class="mt-3 text-sm font-bold text-rose-600">Pilih satu sekolah dari hasil pencarian terlebih dahulu.</p>
                    <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                        <button type="button" class="btn-secondary" @click="closeModal()">Batal</button>
                        <button class="btn-primary" @click="submitTried = true; if (!selected.npsn) $event.preventDefault()">Simpan sekolah</button>
                    </div>
                </form>
            </section>
        </div>
    </template>
</div>
@endsection

