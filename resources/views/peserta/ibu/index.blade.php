@extends('layouts.peserta')

@section('title', 'Data Ibu')
@section('page_title', 'Langkah 4/7 - Data Ibu')

@section('content')
<div class="mx-auto max-w-7xl">
    <x-step-indicator currentStep="4" />

    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm sm:p-6 md:p-8">
        <h2 class="text-xl font-bold text-slate-800 mb-1">Data Ibu Kandung</h2>
        <p class="text-sm text-slate-500 mb-6">Isi data ibu kandung calon peserta didik.</p>

        <form method="POST" action="{{ route('peserta.ibu') }}" class="space-y-4 md:space-y-5" novalidate>
            @csrf

            <div>
                <label for="nama" class="block text-xs font-semibold text-slate-500 mb-1.5">Nama Lengkap Ibu <span class="text-rose-500">*</span></label>
                <input id="nama" type="text" name="nama" value="{{ old('nama', $ibu->name ?? '') }}" required
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all"
                       placeholder="Sesuai KTP">
                @error('nama') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="nik" class="block text-xs font-semibold text-slate-500 mb-1.5">NIK (No. KTP)</label>
                <input id="nik" type="text" name="nik" value="{{ old('nik', $ibu->nik ?? '') }}"
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all"
                       placeholder="16 digit NIK" maxlength="20">
            </div>

            <x-form-select name="pekerjaan" label="Pekerjaan" :options="$pekerjaans" :value="$ibu->occupation ?? ''" placeholder="Pilih pekerjaan ibu" />

            <x-form-select name="pendidikan" label="Pendidikan Terakhir" :options="$pendidikans" :value="$ibu->education ?? ''" placeholder="Pilih pendidikan terakhir" />

            <x-form-select name="penghasilan" label="Penghasilan per Bulan" :options="$penghasilans" :value="$ibu->income ?? ''" placeholder="Pilih rentang penghasilan" />

            <div>
                <label for="no_hp" class="block text-xs font-semibold text-slate-500 mb-1.5">No. HP / WhatsApp</label>
                <input id="no_hp" type="text" name="no_hp" value="{{ old('no_hp', $ibu->phone ?? '') }}"
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all"
                       placeholder="08xxxxxxxxxx">
            </div>

            <div class="flex flex-col gap-3 pt-4 border-t border-slate-100 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('peserta.ayah') }}" class="btn-link-back">Kembali</a>
                <button type="submit" class="btn-primary">
                    Simpan & Lanjut
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
