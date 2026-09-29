@extends(auth()->user()?->hasRole('kepala_sekolah') ? 'layouts.kepala-sekolah' : (auth()->user()?->hasRole('bendahara') ? 'layouts.bendahara' : (request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.panitia')))

@section('title', 'Kunjungan Calon Siswa')
@section('page_title', 'Kunjungan Calon Siswa')
@section('page_description', 'Catat calon siswa yang datang dan pantau data kunjungannya.')

@section('content')
@php
    $role = auth()->user()?->role?->name;
    $prefix = match (true) {
        request()->routeIs('admin.*') => 'admin.',
        request()->routeIs('bendahara.*') => 'bendahara.',
        request()->routeIs('kepala-sekolah.*') => 'kepala-sekolah.',
        default => 'panitia.',
    };
    $readOnly = in_array($role, ['bendahara', 'kepala_sekolah'], true);
    $exportButtonClass = match ($role) {
        'panitia' => 'bg-violet-700 hover:bg-violet-800 focus:ring-violet-200',
        'kepala_sekolah' => 'bg-teal-700 hover:bg-teal-800 focus:ring-teal-200',
        'bendahara' => 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-200',
        default => 'bg-blue-700 hover:bg-blue-800 focus:ring-blue-200',
    };
@endphp
<div class="visit-workspace mx-auto max-w-[1180px]" x-data="Object.assign({ createOpen: {{ $errors->any() ? 'true' : 'false' }} }, visitSchoolPicker(@js(route($prefix . 'kunjungan.sekolah.search'))))">
    <section class="visit-overview">
        <div>
            <p class="visit-kicker">Buku kunjungan</p>
            <h2>Calon siswa datang</h2>
            <p>Catat kedatangan dalam buku kunjungan bersama dan ketahui petugas yang menerimanya.</p>
        </div>
        <div class="visit-overview-stats"><span><b>{{ $visitsToday }}</b>hari ini</span><span><b>{{ $unregisteredCount }}</b>belum daftar</span></div>
    </section>

    <section class="visit-list admin-card overflow-hidden">
        <div class="admin-card-header visit-list-header">
            <div>
                <h2 class="text-xl font-black text-slate-950">Daftar kunjungan</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $visits->total() }} data · Ditampilkan untuk seluruh petugas</p>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="{{ route($prefix.'kunjungan.export', request()->only('search')) }}" class="inline-flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-black text-white shadow-sm transition focus:outline-none focus:ring-4 {{ $exportButtonClass }}" title="Unduh daftar kunjungan ke Excel">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Export Excel
                </a>
                @unless($readOnly)
                    <button type="button" @click="createOpen = true" class="visit-create-button">+ Catat kunjungan</button>
                @endunless
            </div>
        </div>
        <div class="mx-4 mb-3 flex flex-col gap-2 lg:flex-row lg:items-center">
            <form method="GET" class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                <x-list-search placeholder="Cari siswa, nomor WhatsApp, atau sekolah" class="flex-1" />
                <button class="visit-search-button visit-search-{{ auth()->user()?->role?->name ?? 'panitia' }}">Cari</button>
                @if(request('search'))<a href="{{ route($prefix.'kunjungan.index') }}" class="rounded-xl bg-slate-100 px-4 py-2.5 text-center text-xs font-black text-slate-600">Reset</a>@endif
            </form>
            <x-per-page-pagination :paginator="$visits" static />
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table min-w-[1120px]">
                <thead><tr><th>Siswa</th><th>Orang tua/wali</th><th>Sekolah asal</th><th>Minat jurusan</th><th>Datang</th><th>Petugas penerima</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse($visits as $visit)
                        <tr>
                            <td><p class="font-black text-slate-950">{{ $visit->full_name }}</p><p class="text-xs text-slate-500">{{ $visit->visitor_phone }}</p></td>
                            <td><p class="font-bold text-slate-700">{{ $visit->parent_name ?: '-' }}</p><p class="text-xs text-slate-500">{{ $visit->parent_phone ?: '-' }}</p></td>
                            <td><p class="font-bold text-slate-700">{{ $visit->origin_school ?: '-' }}</p>@if($visit->origin_school_npsn)<p class="text-xs text-slate-500">NPSN {{ $visit->origin_school_npsn }}</p>@endif</td>
                            <td>{{ $visit->major_interest ?: '-' }}</td>
                            <td><p class="font-bold text-slate-700">{{ $visit->visited_at?->format('d M Y') ?? '-' }}</p><p class="text-xs text-slate-400">{{ $visit->visited_at?->format('H:i') ?? '' }} WIB</p></td>
                            <td><p class="font-bold text-slate-800">{{ $visit->penerima?->name ?? 'Tidak tercatat' }}</p><p class="text-xs text-slate-500">{{ $visit->penerima?->role?->description ?? 'Petugas penerima' }}</p></td>
                            <td>@if($visit->pendaftar)<span class="visit-status is-linked">Terhubung</span>@else<span class="visit-status">Belum daftar</span>@endif</td>
                            <td class="text-right"><div class="flex justify-end gap-2">@if($visit->pendaftar)<a href="{{ route($prefix . 'pendaftar.show', $visit->pendaftar) }}" class="visit-detail-link">Lihat siswa →</a>@endif @unless($readOnly)<button type="button" onclick="document.getElementById('edit-kunjungan-{{ $visit->id }}').showModal()" class="visit-detail-link">Edit</button>@if(! $visit->pendaftar)<form method="POST" action="{{ route($prefix.'kunjungan.destroy', $visit) }}" onsubmit="return confirm('Hapus data kunjungan {{ addslashes($visit->full_name) }}? Data yang sudah dihapus tidak dapat dikembalikan.')">@csrf @method('DELETE')<button class="text-xs font-black text-rose-600 hover:text-rose-800">Hapus</button></form>@endif @endunless @if(! $visit->pendaftar)<span class="text-xs font-semibold text-slate-400">Menunggu daftar</span>@endif</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-slate-400">Belum ada kunjungan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @unless($readOnly)
            @foreach($visits as $visit)
                <dialog id="edit-kunjungan-{{ $visit->id }}" class="w-[min(94vw,720px)] rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/50">
                    <form method="POST" action="{{ route($prefix.'kunjungan.update', $visit) }}" class="p-5 sm:p-6">
                        @csrf @method('PUT')
                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4"><div><p class="text-xs font-black uppercase tracking-[.14em] text-violet-700">Perbaiki data</p><h3 class="mt-1 text-lg font-black text-slate-950">Edit kunjungan siswa</h3><p class="mt-1 text-sm text-slate-500">Gunakan edit bila ada salah ketik atau data dobel.</p></div><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100">Tutup</button></div>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <label class="admin-label sm:col-span-2">Tujuan kedatangan<select name="visit_purpose" required class="admin-input mt-1"><option value="information" @selected($visit->visit_purpose === 'information')>Bertanya</option><option value="plan_to_register" @selected($visit->visit_purpose === 'plan_to_register')>Rencana daftar</option><option value="direct_registration" @selected($visit->visit_purpose === 'direct_registration')>Langsung daftar</option></select></label>
                            <label class="admin-label">Nama calon siswa<input name="full_name" value="{{ $visit->full_name }}" required class="admin-input mt-1"></label>
                            <label class="admin-label">No. WhatsApp<input name="visitor_phone" value="{{ $visit->visitor_phone }}" required inputmode="numeric" class="admin-input mt-1"></label>
                            <label class="admin-label">Nama orang tua/wali<input name="parent_name" value="{{ $visit->parent_name }}" class="admin-input mt-1"></label>
                            <label class="admin-label">No. HP orang tua/wali<input name="parent_phone" value="{{ $visit->parent_phone }}" inputmode="numeric" class="admin-input mt-1"></label>
                            <label class="admin-label sm:col-span-2">Sekolah asal<select name="referensi_sekolah_id" required class="admin-input mt-1"><option value="">Pilih sekolah</option>@foreach($schools as $school)<option value="{{ $school->id }}" @selected($visit->school_reference_id === $school->id)>{{ $school->nama }} · {{ $school->npsn }}{{ $school->kecamatan ? ' · Kec. '.$school->kecamatan : '' }}</option>@endforeach</select></label>
                            <label class="admin-label">Jurusan diminati<select name="interested_major_id" required class="admin-input mt-1">@foreach($jurusans as $jurusan)<option value="{{ $jurusan->id }}" @selected($visit->major_interest === $jurusan->name)>{{ $jurusan->name }}</option>@endforeach</select></label>
                            <label class="admin-label">Catatan<input name="notes" value="{{ $visit->notes }}" class="admin-input mt-1"></label>
                        </div>
                        <div class="mt-6 flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100">Batal</button><button class="rounded-xl bg-violet-700 px-4 py-2.5 text-sm font-black text-white hover:bg-violet-800">Simpan perubahan</button></div>
                    </form>
                </dialog>
            @endforeach
        @endunless
    </section>

    @unless($readOnly)
    <template x-teleport="body"><div x-cloak x-show="createOpen" x-transition.opacity @keydown.escape.window="createOpen = false" class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-950/55 p-3 sm:p-4" @click.self="createOpen = false">
        <section role="dialog" aria-modal="true" aria-label="Catat kunjungan baru" class="visit-create-dialog grid max-h-[calc(100vh-32px)] w-full max-w-4xl grid-rows-[auto_minmax(0,1fr)_auto] overflow-hidden rounded-2xl bg-white shadow-2xl sm:max-h-[calc(100vh-32px)]">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6"><div><h2 class="text-lg font-black text-slate-950">Catat kunjungan</h2><p class="text-sm text-slate-500">Penerima: {{ Auth::user()->name }}</p></div><button type="button" @click="createOpen = false" class="rounded-lg px-3 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100">Tutup</button></div>
            <form id="visit-create-form" method="POST" action="{{ route($prefix . 'kunjungan.store') }}" class="min-h-0 overflow-y-auto p-5 sm:p-6" autocomplete="off" @submit="createOpen = false">
                @csrf
                <input type="hidden" name="referensi_sekolah_id" :value="selectedId">
                <div class="grid gap-3 sm:grid-cols-2">
                    <fieldset class="sm:col-span-2"><legend class="admin-label">Tujuan kedatangan</legend><div class="grid gap-2 sm:grid-cols-3">@foreach(['information' => 'Bertanya', 'plan_to_register' => 'Rencana daftar', 'direct_registration' => 'Langsung daftar'] as $value => $label)<label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold"><input type="radio" name="visit_purpose" value="{{ $value }}" @checked(old('visit_purpose', 'information') === $value)>{{ $label }}</label>@endforeach</div></fieldset>
                    <label class="admin-label">Nama calon siswa<input name="full_name" value="{{ old('full_name') }}" required class="admin-input mt-1" placeholder="Nama lengkap">@error('full_name')<p class="admin-error">{{ $message }}</p>@enderror</label>
                    <label class="admin-label">No. WhatsApp<input name="visitor_phone" value="{{ old('visitor_phone') }}" required inputmode="numeric" class="admin-input mt-1" placeholder="08xxxxxxxxxx">@error('visitor_phone')<p class="admin-error">{{ $message }}</p>@enderror</label>
                    <label class="admin-label">Nama orang tua/wali <span class="font-normal text-slate-400">(isi salah satu)</span><input name="parent_name" value="{{ old('parent_name') }}" class="admin-input mt-1" placeholder="Nama orang tua atau wali">@error('parent_name')<p class="admin-error">{{ $message }}</p>@enderror</label>
                    <label class="admin-label">No. HP orang tua/wali <span class="font-normal text-slate-400">(isi salah satu)</span><input name="parent_phone" value="{{ old('parent_phone') }}" inputmode="numeric" class="admin-input mt-1" placeholder="08xxxxxxxxxx">@error('parent_phone')<p class="admin-error">{{ $message }}</p>@enderror</label>
                    <div class="rounded-2xl border border-sky-100 bg-sky-50/70 p-3 sm:col-span-2"><div class="mb-2 flex items-center justify-between gap-2"><label class="admin-label mb-0 text-sky-900">Cari sekolah asal <span class="text-rose-500">*</span></label><span class="text-xs font-semibold text-sky-700">Hanya SMP/MTs</span></div>
                        <div x-show="!manual" class="relative"><input x-ref="schoolInput" type="text" x-model="query" @input.debounce.150ms="search()" @focus="if (query.trim().length >= 1) { open = true; $nextTick(() => positionSchoolMenu()) }" @keydown.escape="open=false" class="admin-input bg-white" autocomplete="off" spellcheck="false" placeholder="Ketik nama SMP/MTs, NPSN, atau kecamatan"><template x-teleport="body"><div x-cloak x-show="open" @click.outside="open=false" class="fixed z-[1100] overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-2xl" :style="schoolMenuStyle"><p x-show="loading" class="p-3 text-sm font-bold text-slate-500">Mencari SMP/MTs...</p><p x-show="!loading && query.trim().length >= 1 && results.length === 0" class="p-3 text-sm font-semibold leading-relaxed text-slate-600">SMP/MTs tidak ditemukan. Periksa kembali nama sekolah, NPSN, atau kecamatan.</p><template x-for="school in results" :key="school.id"><button type="button" @click="selectSchool(school)" class="block w-full border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-sky-50"><span class="block text-sm font-black text-slate-900" x-text="school.nama"></span><span class="mt-1 block text-xs font-bold text-sky-700" x-text="(school.bentuk_pendidikan || 'SMP/MTs') + ' · NPSN ' + school.npsn"></span><span class="mt-1 block text-xs font-semibold text-slate-500" x-text="[school.kecamatan, school.kabupaten_kota].filter(Boolean).join(', ') || 'Wilayah belum tersedia'"></span></button></template></div></template></div>
                        <div x-cloak x-show="selectedName" class="mt-2 rounded-xl bg-emerald-50 p-3"><p class="font-black" x-text="selectedName"></p><p class="text-xs text-emerald-700" x-text="'NPSN ' + selectedNpsn"></p><button type="button" @click="clearSchool()" class="mt-1 text-xs font-black text-sky-700">Ganti sekolah</button></div>
                        @error('referensi_sekolah_id')<p class="admin-error">{{ $message }}</p>@enderror</div>
                    <label class="admin-label">Jurusan diminati<x-form-select name="interested_major_id" menuClass="visit-major-menu" :menuZIndex="1100" :forceDown="true" :options="$jurusans->map(fn ($jurusan) => ['value' => $jurusan->id, 'label' => $jurusan->name])" :value="old('interested_major_id')" placeholder="Pilih jurusan" required />@error('interested_major_id')<p class="admin-error">{{ $message }}</p>@enderror</label>
                    <label class="admin-label">Catatan <span class="font-normal text-slate-400">(opsional)</span><input name="notes" value="{{ old('notes') }}" class="admin-input mt-1" placeholder="Jika ada info tambahan"></label>
                </div>
            </form>
            <div class="flex shrink-0 justify-end gap-2 border-t border-slate-100 bg-white px-3 py-2.5 pb-[max(10px,env(safe-area-inset-bottom))] sm:px-4"><button type="button" @click="createOpen = false" class="rounded-xl px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100">Batal</button><button type="submit" form="visit-create-form" class="admin-primary-button bg-emerald-600 hover:bg-emerald-700">Simpan kunjungan</button></div>
        </section>
    </div></template>
    @endunless
</div>
@endsection

@push('scripts')
<script>
function visitSchoolPicker(searchUrl) { return {
    searchUrl, query:'', results:[], loading:false, open:false, manual:false, schoolMenuStyle:'',
    selectedId:'', selectedName:'', selectedNpsn:'', selectedAddress:'',
    positionSchoolMenu(){ const rect=this.$refs.schoolInput?.getBoundingClientRect(); if(!rect) return; const below=Math.max(96,window.innerHeight-rect.bottom-12); const height=Math.min(220,below); this.schoolMenuStyle=`left:${rect.left}px;top:${rect.bottom+6}px;width:${rect.width}px;max-height:${height}px;`; },
    async search() { this.selectedId=''; this.selectedName=''; if(this.query.trim().length<1){this.results=[];this.open=false;return;} this.loading=true; this.open=true; this.$nextTick(() => this.positionSchoolMenu()); try { const response=await fetch(`${this.searchUrl}?q=${encodeURIComponent(this.query.trim())}`,{headers:{Accept:'application/json'}}); this.results=await response.json(); } catch(e){this.results=[];} finally{this.loading=false;this.$nextTick(() => this.positionSchoolMenu());} },
    selectSchool(school){this.selectedId=school.id;this.selectedName=school.nama;this.selectedNpsn=school.npsn;this.selectedAddress=school.alamat||'';this.query=`${school.nama} - ${school.npsn}`;this.results=[];this.open=false;},
    clearSchool(){this.selectedId='';this.selectedName='';this.selectedNpsn='';this.query='';},
} }
</script>
@endpush
