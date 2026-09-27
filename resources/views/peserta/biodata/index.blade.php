@extends('layouts.peserta')

@section('title', 'Biodata Diri')
@section('page_title', 'Langkah 1/7 - Biodata Diri')

@section('content')
@php
    $enabledFields = \App\Support\FormFieldCatalog::enabled();
@endphp
<div class="mx-auto max-w-7xl">

    <x-step-indicator currentStep="1" />

    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm sm:p-6 md:p-8">
        <h2 class="text-xl font-bold text-slate-800 mb-1">Biodata Diri</h2>
        <p class="text-sm text-slate-500 mb-6">Isi data diri calon peserta didik baru.</p>

        <form method="POST" action="{{ route('peserta.biodata') }}" class="space-y-4 md:space-y-5" novalidate>
            @csrf

            <div>
                <label for="nama_lengkap" class="block text-xs font-semibold text-slate-500 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input id="nama_lengkap" type="text" value="{{ auth()->user()->name }}" readonly
                       class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700">
                <p class="mt-1 text-xs text-slate-400">Otomatis mengikuti nama pada akun yang sedang login.</p>
                @error('nama_lengkap') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="nisn" class="block text-xs font-semibold text-slate-500 mb-1.5">NISN <span class="text-rose-500">*</span></label>
                <input id="nisn" type="text" name="nisn"
                       value="{{ old('nisn', $biodata->nisn ?? '') }}"
                       inputmode="numeric"
                       maxlength="10"
                       required
                       title="NISN harus terdiri dari tepat 10 digit angka."
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all"
                       placeholder="10 digit NISN">
                <p class="mt-1 text-xs text-slate-400">
                    NISN terdiri dari 10 digit angka. Cek di rapor, ijazah/SKL, kartu pelajar, atau
                    <a href="https://nisn.data.kemendikdasmen.go.id/" target="_blank" rel="noopener" class="font-semibold text-sky-600 hover:text-sky-700">cari di situs NISN resmi</a>.
                </p>
                @error('nisn') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="nik" class="block text-xs font-semibold text-slate-500 mb-1.5">NIK <span class="text-slate-400">(KTP calon siswa)</span> <span class="text-rose-500">*</span></label>
                <input id="nik" type="text" name="nik"
                       value="{{ old('nik', $biodata->nik ?? '') }}"
                       inputmode="numeric"
                       maxlength="16"
                       required
                       title="NIK harus terdiri dari tepat 16 digit angka."
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all"
                       placeholder="16 digit NIK">
                <p class="mt-1 text-xs text-slate-400">Lihat NIK di Kartu Keluarga atau KTP calon siswa.</p>
                @error('nik') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="no_kk" class="block text-xs font-semibold text-slate-500 mb-1.5">Nomor Kartu Keluarga <span class="text-rose-500">*</span></label>
                <input id="no_kk" type="text" name="no_kk"
                       value="{{ old('no_kk', $biodata->family_card_number ?? $biodata->no_kk ?? '') }}"
                       inputmode="numeric" maxlength="16" required
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all"
                       placeholder="16 digit nomor KK">
                @error('no_kk') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1.5">Jenis Kelamin <span class="text-rose-500">*</span></label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="jenis_kelamin" value="L"
                               required
                               {{ old('jenis_kelamin', $biodata->gender ?? '') === 'L' ? 'checked' : '' }}
                               class="text-sky-600 focus:ring-sky-500">
                        <span class="text-sm text-slate-700">Laki-laki</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="jenis_kelamin" value="P"
                               {{ old('jenis_kelamin', $biodata->gender ?? '') === 'P' ? 'checked' : '' }}
                               class="text-sky-600 focus:ring-sky-500">
                        <span class="text-sm text-slate-700">Perempuan</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="tempat_lahir" class="block text-xs font-semibold text-slate-500 mb-1.5">Tempat Lahir <span class="text-rose-500">*</span></label>
                    <input id="tempat_lahir" type="text" name="tempat_lahir"
                           value="{{ old('tempat_lahir', $biodata->birth_place ?? '') }}"
                           required
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all"
                           placeholder="Kota kelahiran">
                </div>
                <div>
                    <label for="tanggal_lahir" class="block text-xs font-semibold text-slate-500 mb-1.5">Tanggal Lahir <span class="text-rose-500">*</span></label>
                    <input id="tanggal_lahir" type="date" name="tanggal_lahir"
                           value="{{ old('tanggal_lahir', isset($biodata->birth_date) ? date('Y-m-d', strtotime($biodata->birth_date)) : '') }}"
                           required
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all">
                </div>
            </div>

            <x-form-select
                name="agama"
                label="Agama"
                :options="$agamas"
                :value="$biodata->religion ?? ''"
                placeholder="Pilih Agama"
            />

            <div class="flex flex-col gap-3 pt-4 border-t border-slate-100 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('peserta.dashboard') }}" class="btn-link-back">
                    Kembali ke Dashboard
                </a>
                <button type="submit" data-submit-label="Simpan & Lanjut" class="btn-primary">
                    Simpan & Lanjut
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
