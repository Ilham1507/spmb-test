@extends('layouts.peserta')

@section('title', 'Sekolah Asal')
@section('page_title', 'Langkah 5/7 - Sekolah Asal')

@section('content')
<div class="mx-auto max-w-7xl">

    <x-step-indicator currentStep="5" />

    <div
        class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm sm:p-6 md:p-8"
        x-data="schoolPicker({
            searchUrl: @js(route('peserta.sekolah.search')),
            initialName: @js(old('nama_sekolah', $sekolah->school_name ?? '')),
            initialNpsn: @js(old('npsn', $sekolah->npsn ?? '')),
            initialAddress: @js(old('alamat_sekolah', $sekolah->school_address ?? '')),
            initialEducationForm: @js(old('bentuk_pendidikan', $sekolah->bentuk_pendidikan ?? '')),
            initialStatus: @js(old('status_sekolah', $sekolah->status_sekolah ?? '')),
            initialVillage: @js(old('desa_kelurahan', $sekolah->desa_kelurahan ?? '')),
            initialDistrict: @js(old('kecamatan', $sekolah->kecamatan ?? '')),
            initialCity: @js(old('kabupaten_kota', $sekolah->kabupaten_kota ?? '')),
            initialProvince: @js(old('provinsi', $sekolah->provinsi ?? '')),
            initialSelectedId: @js($sekolah->referensi_sekolah_id ?? '')
        })"
    >
        <h2 class="text-xl font-bold text-slate-800 mb-1">Sekolah Asal</h2>
        <div class="mb-6"></div>

        <form method="POST" action="{{ route('peserta.sekolah') }}" class="space-y-4 md:space-y-5" @submit="if (!selectedId) { $event.preventDefault(); selectionError = true; $nextTick(() => document.getElementById('search_sekolah').focus()); }">
            @csrf

            <input type="hidden" name="referensi_sekolah_id" :value="selectedId">

            <div class="rounded-2xl border border-sky-100 bg-sky-50/70 p-4">
                <label for="search_sekolah" class="mb-2 block text-xs font-black uppercase tracking-wide text-sky-800">Cari SMP / MTs berdasarkan nama atau NPSN <span class="text-rose-500">*</span></label>

                <div class="relative">
                    <input id="search_sekolah" type="text" x-model="query" @input.debounce.150ms="search()" @focus="open = true"
                           required
                           class="w-full px-4 py-3 rounded-xl border border-sky-200 bg-white focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all"
                           placeholder="Cari SMP/MTs, NPSN, atau kecamatan">

                    <div x-cloak x-show="open" @click.outside="open = false" class="absolute left-0 right-0 top-[calc(100%+8px)] z-30 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                        <div x-show="loading" class="px-4 py-3 text-sm font-semibold text-slate-500">Mencari sekolah...</div>

                        <template x-if="!loading && results.length === 0 && query.length >= 1">
                            <div class="px-4 py-4">
                                <p class="text-sm font-bold text-slate-700">Sekolah belum ditemukan.</p>
                                <p class="mt-1 text-xs leading-relaxed text-slate-500">Coba nama sekolah, NPSN, atau kecamatan.</p>
                            </div>
                        </template>

                        <template x-for="school in results" :key="school.id">
                            <button type="button" class="block w-full border-b border-slate-100 px-4 py-3 text-left transition last:border-b-0 hover:bg-sky-50" @click="selectSchool(school)">
                                <span class="block text-sm font-black text-slate-800" x-text="school.nama"></span>
                                <span class="mt-1 flex flex-wrap gap-2 text-[11px] font-bold text-slate-500">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5" x-text="'NPSN ' + school.npsn"></span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5" x-text="school.bentuk_pendidikan || 'Sekolah'"></span>
                                    <span class="rounded-full bg-sky-100 px-2 py-0.5 text-sky-800" x-show="school.kecamatan" x-text="'Kec. ' + school.kecamatan"></span>
                                </span>
                                <span class="mt-1 block text-xs leading-relaxed text-slate-500" x-text="school.alamat_lengkap || 'Alamat belum tersedia di referensi.'"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <p x-cloak x-show="selectionError" class="mt-2 text-xs font-semibold text-rose-600">Pilih sekolah dari hasil pencarian.</p>
                @error('referensi_sekolah_id') <p class="text-xs text-rose-500 mt-2">{{ $message }}</p> @enderror
            </div>

            <div x-show="selectedName" x-cloak class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                <p class="text-xs font-black uppercase tracking-wide text-emerald-700">Sekolah dipilih</p>
                <p class="mt-1 text-lg font-black text-slate-900" x-text="selectedName"></p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl bg-white p-3 ring-1 ring-emerald-100">
                        <span class="text-xs font-bold text-slate-400">NPSN</span>
                        <p class="font-black text-slate-800" x-text="selectedNpsn || '-'"></p>
                    </div>
                    <div class="rounded-xl bg-white p-3 ring-1 ring-emerald-100">
                        <span class="text-xs font-bold text-slate-400">Bentuk / Status</span>
                        <p class="font-semibold leading-relaxed text-slate-700" x-text="[selectedEducationForm, selectedStatus].filter(Boolean).join(' · ') || '-' "></p>
                    </div>
                    <div class="rounded-xl bg-white p-3 ring-1 ring-emerald-100 sm:col-span-2 lg:col-span-1">
                        <span class="text-xs font-bold text-slate-400">Alamat sekolah</span>
                        <p class="font-semibold leading-relaxed text-slate-700" x-text="selectedAddress || 'Alamat belum tersedia.'"></p>
                    </div>
                    <div class="rounded-xl bg-white p-3 ring-1 ring-emerald-100 sm:col-span-2 lg:col-span-3">
                        <span class="text-xs font-bold text-slate-400">Wilayah</span>
                        <p class="font-semibold leading-relaxed text-slate-700" x-text="[selectedDistrict, selectedCity, selectedProvince].filter(Boolean).join(', ') || 'Wilayah belum tersedia.'"></p>
                    </div>
                </div>
                <button type="button" class="mt-3 text-xs font-black text-sky-700 hover:text-sky-800" @click="clearSelection()">Ganti sekolah</button>
            </div>

            <div>
                <label for="tahun_lulus" class="block text-xs font-semibold text-slate-500 mb-1.5">Tahun Lulus</label>
                <input id="tahun_lulus" type="number" name="tahun_lulus"
                       required
                       value="{{ old('tahun_lulus', $sekolah->graduation_year ?? date('Y')) }}"
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all"
                       placeholder="Contoh: 2026">
            </div>

            <div class="flex flex-col gap-3 pt-4 border-t border-slate-100 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('peserta.ibu') }}" class="btn-link-back">Kembali</a>
                <button type="submit" class="btn-primary">
                    Simpan & Lanjut
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function schoolPicker(config) {
        return {
            searchUrl: config.searchUrl,
            query: config.initialName || '',
            results: [],
            loading: false,
            open: false,
            selectionError: false,
            selectedId: config.initialSelectedId || '',
            selectedName: config.initialName || '',
            selectedNpsn: config.initialNpsn || '',
            selectedAddress: config.initialAddress || '',
            selectedEducationForm: config.initialEducationForm || '',
            selectedStatus: config.initialStatus || '',
            selectedVillage: config.initialVillage || '',
            selectedDistrict: config.initialDistrict || '',
            selectedCity: config.initialCity || '',
            selectedProvince: config.initialProvince || '',
            async search() {
                this.selectedId = '';
                this.selectedName = '';
                this.selectedNpsn = '';
                this.selectedAddress = '';
                this.selectedEducationForm = '';
                this.selectedStatus = '';
                this.selectedVillage = '';
                this.selectedDistrict = '';
                this.selectedCity = '';
                this.selectedProvince = '';

                if (this.query.trim().length < 1) {
                    this.results = [];
                    return;
                }

                this.loading = true;
                this.open = true;

                try {
                    const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(this.query.trim())}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    this.results = await response.json();
                } catch (error) {
                    this.results = [];
                } finally {
                    this.loading = false;
                }
            },
            selectSchool(school) {
                this.selectedId = school.id;
                this.selectionError = false;
                this.selectedName = school.nama;
                this.selectedNpsn = school.npsn;
                this.selectedAddress = school.alamat || '';
                this.selectedEducationForm = school.bentuk_pendidikan || '';
                this.selectedStatus = school.status || '';
                this.selectedVillage = school.desa_kelurahan || '';
                this.selectedDistrict = school.kecamatan || '';
                this.selectedCity = school.kabupaten_kota || '';
                this.selectedProvince = school.provinsi || '';
                this.query = `${school.nama} - ${school.npsn}`;
                this.results = [];
                this.open = false;
            },
            clearSelection() {
                this.selectedId = '';
                this.selectedName = '';
                this.selectedNpsn = '';
                this.selectedAddress = '';
                this.selectedEducationForm = '';
                this.selectedStatus = '';
                this.selectedVillage = '';
                this.selectedDistrict = '';
                this.selectedCity = '';
                this.selectedProvince = '';
                this.query = '';
                this.open = false;
            }
        };
    }
</script>
@endpush
