@extends('layouts.peserta')

@section('title', 'Pilihan Jurusan')
@section('page_title', 'Langkah 6/7 - Pilihan Jurusan & Jalur')

@section('content')
@php
    $jurusanUtamaOptions = $jurusans->mapWithKeys(fn ($j) => [
        $j->id => $j->name,
    ]);
    $jurusanCadanganOptions = $jurusans->mapWithKeys(fn ($j) => [
        $j->id => $j->name,
    ]);
@endphp

<div class="mx-auto max-w-7xl">
    <x-step-indicator currentStep="6" />

    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm sm:p-6 md:p-8">
        <h2 class="text-xl font-bold text-slate-800 mb-1">Pilihan Jurusan & Jalur Masuk</h2>
        <p class="text-sm text-slate-500 mb-6">
            Pilih dari jurusan aktif yang tersedia di database sekolah.
        </p>

        @if($jurusans->count() !== 4)
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-800">
                Catatan admin: jumlah jurusan aktif saat ini {{ $jurusans->count() }}. Pastikan master jurusan aktif hanya 4 sesuai program sekolah.
            </div>
        @endif

        <form method="POST" action="{{ route('peserta.jurusan') }}" class="space-y-4 md:space-y-5">
            @csrf

            <x-form-select
                name="jurusan_id_1"
                id="jurusan-pilihan-utama"
                label="Jurusan Pilihan 1"
                :options="$jurusanUtamaOptions"
                :value="$pendaftar->major_choice_1"
                placeholder="Pilih Jurusan Utama"
                required
            />

            @if(($maximumChoices ?? 2) > 1)
                <x-form-select
                    name="jurusan_id_2"
                    label="Jurusan Pilihan 2 (Cadangan)"
                    :options="$jurusanCadanganOptions"
                    :value="$pendaftar->major_choice_2"
                    placeholder="Pilih Jurusan Cadangan"
                />
            @endif

            <div>
                <label class="mb-3 block text-xs font-semibold text-slate-500">Jalur Pendaftaran <span class="text-rose-500">*</span></label>
                <div class="space-y-3">
                    @foreach($jalurs as $jl)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-all {{ old('jalur_pendaftaran_id', $pendaftar->admission_path_id) == $jl->id ? 'border-sky-500 bg-sky-50 ring-2 ring-sky-500/20' : 'border-slate-200 hover:bg-slate-50' }}">
                            <input type="radio" name="jalur_pendaftaran_id" value="{{ $jl->id }}"
                                   {{ old('jalur_pendaftaran_id', $pendaftar->admission_path_id) == $jl->id ? 'checked' : '' }}
                                   class="mt-0.5 text-sky-600 focus:ring-sky-500">
                            <div>
                                <span class="text-sm font-bold text-slate-800">{{ $jl->name }}</span>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $jl->description }}</p>
                                @if($jl->quota)
                                    <span class="mt-1 inline-block rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-600">Kuota: {{ $jl->quota }}</span>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('jalur_pendaftaran_id') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('peserta.sekolah') }}" class="btn-link-back">Kembali</a>
                <button type="submit" class="btn-primary">
                    Simpan & Lanjut
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
