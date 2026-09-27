@extends('layouts.peserta')

@section('title', 'Upload Dokumen')
@section('page_title', 'Langkah 7/7 - Upload Dokumen')

@section('content')
<div class="mx-auto max-w-7xl">

    <x-step-indicator currentStep="7" />

    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm sm:p-6 md:p-8">
        <h2 class="text-xl font-bold text-slate-800">Upload Dokumen</h2>
        <p class="mt-2 mb-6 text-sm text-slate-500">JPG, PNG, atau PDF · Maks. 2 MB per file</p>

        <form method="POST" action="{{ route('peserta.dokumen') }}" enctype="multipart/form-data" class="space-y-4 md:space-y-5" novalidate>
            @csrf

            @foreach($jenisDokumens as $jenis)
                @php
                    $inputName = 'dokumen_' . $jenis->id;
                    $uploaded = $uploadedDocs->get($jenis->id);
                @endphp

                <div class="rounded-xl border p-4 {{ $uploaded ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-200 bg-white' }}">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <label for="{{ $inputName }}" class="block text-sm font-bold text-slate-700">
                            {{ $jenis->name }}
                            @if($jenis->is_required)
                                <span class="text-rose-500">*</span>
                            @else
                                <span class="ml-1 text-xs font-normal text-slate-400">(Opsional)</span>
                            @endif
                        </label>

                        @if($uploaded)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-600">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                Sudah diunggah
                            </span>
                        @endif
                    </div>

                    <input id="{{ $inputName }}" type="file" name="{{ $inputName }}" accept=".jpg,.jpeg,.png,.pdf"
                           class="mt-3 block w-full cursor-pointer text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-sky-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-sky-700 transition-all hover:file:bg-sky-100">
                    @error($inputName) <p class="mt-1 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('peserta.jurusan') }}" class="btn-link-back">Kembali</a>
                <button type="submit" class="btn-primary">
                    Simpan & Lanjut ke Review
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
