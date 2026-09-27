@extends('layouts.peserta')

@section('title', 'Alamat')
@section('page_title', 'Langkah 2/7 - Alamat')

@section('content')
<div class="mx-auto max-w-7xl">

    <x-step-indicator currentStep="2" />

    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm sm:p-6 md:p-8">
        <h2 class="text-xl font-bold text-slate-800 mb-1">Alamat Tempat Tinggal</h2>
        <p class="text-sm text-slate-500 mb-6">Isi alamat lengkap sesuai domisili saat ini.</p>

        <form method="POST" action="{{ route('peserta.alamat') }}" class="space-y-4 md:space-y-5" novalidate>
            @csrf
            @php
                $distanceOptions = [
                    '0 - 1000 meter' => '0 - 1000 meter',
                    '1001 - 3000 meter' => '1001 - 3000 meter',
                    '3001 - 5000 meter' => '3001 - 5000 meter',
                    '5001 - 10000 meter' => '5001 - 10000 meter',
                    'Lebih dari 10000 meter' => 'Lebih dari 10000 meter',
                ];
                $distancePoints = [
                    '0 - 1000 meter' => 500,
                    '1001 - 3000 meter' => 400,
                    '3001 - 5000 meter' => 300,
                    '5001 - 10000 meter' => 200,
                    'Lebih dari 10000 meter' => 100,
                ];
                $selectedDistance = old('jarak_ke_sekolah', $alamat->distance_range ?? $alamat->distance_to_school ?? null);
                $selectedDistancePoint = $alamat->distance_point ?? ($distancePoints[$selectedDistance] ?? null);
            @endphp

            {{-- Alamat --}}
            <div>
                <label for="alamat" class="block text-xs font-semibold text-slate-500 mb-1.5">Alamat Lengkap <span class="text-rose-500">*</span></label>
                <textarea id="alamat" name="alamat" rows="3" required
                          class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all resize-none"
                          placeholder="Jl. Contoh No. 123">{{ old('alamat', $alamat->address ?? '') }}</textarea>
                @error('alamat') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- RT / RW --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="rt" class="block text-xs font-semibold text-slate-500 mb-1.5">RT</label>
                    <input id="rt" type="text" name="rt" value="{{ old('rt', $alamat->rt ?? '') }}"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all"
                           placeholder="001">
                </div>
                <div>
                    <label for="rw" class="block text-xs font-semibold text-slate-500 mb-1.5">RW</label>
                    <input id="rw" type="text" name="rw" value="{{ old('rw', $alamat->rw ?? '') }}"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all"
                           placeholder="002">
                </div>
            </div>

            {{-- Kelurahan / Kecamatan --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="kelurahan" class="block text-xs font-semibold text-slate-500 mb-1.5">Kelurahan / Desa</label>
                    <input id="kelurahan" type="text" name="kelurahan" value="{{ old('kelurahan', $alamat->village ?? '') }}"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all">
                </div>
                <div>
                    <label for="kecamatan" class="block text-xs font-semibold text-slate-500 mb-1.5">Kecamatan</label>
                    <input id="kecamatan" type="text" name="kecamatan" value="{{ old('kecamatan', $alamat->district ?? '') }}"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all">
                </div>
            </div>

            {{-- Kota / Provinsi --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="kota" class="block text-xs font-semibold text-slate-500 mb-1.5">Kota / Kabupaten</label>
                    <input id="kota" type="text" name="kota" value="{{ old('kota', $alamat->city ?? '') }}"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all">
                </div>
                <div>
                    <label for="provinsi" class="block text-xs font-semibold text-slate-500 mb-1.5">Provinsi</label>
                    <input id="provinsi" type="text" name="provinsi" value="{{ old('provinsi', $alamat->province ?? '') }}"
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all">
                </div>
            </div>

            {{-- Kode Pos --}}
            <div class="max-w-[200px]">
                <label for="kode_pos" class="block text-xs font-semibold text-slate-500 mb-1.5">Kode Pos</label>
                <input id="kode_pos" type="text" name="kode_pos" value="{{ old('kode_pos', $alamat->postal_code ?? '') }}"
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/40 focus:border-sky-500 text-sm transition-all"
                       placeholder="12345">
            </div>

            <div class="max-w-md">
                <x-form-select
                    name="jarak_ke_sekolah"
                    label="Jarak ke Sekolah"
                    :options="$distanceOptions"
                    :value="$selectedDistance"
                    placeholder="Pilih jarak dari rumah ke sekolah"
                />
                <p class="mt-1 text-xs font-semibold text-slate-400">Pilih perkiraan jarak domisili saat ini ke SMK Muhammadiyah 4.</p>
                @if($selectedDistancePoint)
                    <p class="mt-2 inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700">
                        Poin jarak: {{ $selectedDistancePoint }}
                    </p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex flex-col gap-3 pt-4 border-t border-slate-100 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('peserta.biodata') }}" class="btn-link-back">
                    ← Kembali
                </a>
                <button type="submit"
                        class="btn-primary">
                    Simpan & Lanjut
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
