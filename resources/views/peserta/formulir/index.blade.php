@extends('layouts.peserta')

@section('title', 'Lihat Formulir')
@section('page_title', 'Lihat Formulir')

@section('content')
@php
    $value = function (string $key) use ($pendaftar) {
        if ($key === 'jurusan') return $pendaftar->jurusan1?->name;
        if ($key === 'jenis_kelamin') return match ($pendaftar->biodata?->gender) { 'L' => 'Laki-laki', 'P' => 'Perempuan', default => null };
        return \App\Support\FormFieldCatalog::valueFor($pendaftar, $key);
    };
    $groupMap = ['Sekolah Asal' => 'Sekolah Asal', 'Pilihan Jurusan' => 'Pilihan Jurusan', 'Biodata' => 'Biodata', 'Alamat' => 'Alamat', 'Ayah' => 'Ayah', 'Ibu' => 'Ibu', 'Wali' => 'Wali', 'Kontak' => 'Kontak'];
@endphp
<div class="participant-form-summary mx-auto max-w-6xl space-y-4">
    <div class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-xs font-black uppercase tracking-widest text-sky-700">Ringkasan Pendaftaran</p><h2 class="mt-1 text-2xl font-black text-slate-950">Lihat Formulir</h2><p class="mt-1 text-sm text-slate-600">Menampilkan seluruh field yang sedang aktif di Kelola Formulir.</p></div>
        <div class="flex flex-wrap items-center gap-2 sm:justify-end"><div class="rounded-2xl bg-slate-100 px-4 py-2 text-right"><p class="text-[10px] font-black uppercase tracking-wide text-slate-600">No. Pendaftaran</p><p class="text-sm font-black text-slate-950">{{ $pendaftar->registration_number ?? 'Belum dibuat' }}</p></div><div class="rounded-2xl bg-sky-50 px-4 py-2 text-right"><p class="text-[10px] font-black uppercase tracking-wide text-sky-700">Status</p><p class="text-sm font-black capitalize text-sky-900">{{ str_replace('_', ' ', $pendaftar->registration_status ?? 'draft') }}</p></div></div>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
    @foreach($groups as $group => $fields)
        @continue(!isset($groupMap[$group]))
        @php($visible = collect($fields)->filter(fn ($label, $key) => in_array($key, $enabledFields, true)))
        @if($visible->isNotEmpty())
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                <h3 class="border-b border-slate-200 pb-3 text-lg font-black text-slate-900">Data {{ $group }}</h3>
                <div class="mt-4 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                    @foreach($visible as $key => $label)
                        <div><p class="text-xs font-bold uppercase tracking-wide text-slate-700">{{ $label }}</p><p class="mt-1 break-words text-sm font-semibold text-slate-950">{{ $value($key) ?: '-' }}</p></div>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
    </div>
    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between"><a href="{{ route('peserta.dashboard') }}" class="btn-link-back">Kembali ke Dashboard</a><a href="{{ route('peserta.pdf') }}" class="btn-primary">Simpan sebagai PDF</a></div>
</div>
@endsection
