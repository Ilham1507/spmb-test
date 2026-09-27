@extends('layouts.admin')

@section('title','Konfigurasi SPMB')
@section('page_title','Konfigurasi SPMB')

@section('content')
@php
    $isWithinSchedule = $pengaturan?->status === 'aktif'
        && $pengaturan?->tanggal_buka
        && $pengaturan?->tanggal_tutup
        && now()->betweenIncluded(
            \Carbon\Carbon::parse($pengaturan->tanggal_buka)->startOfDay(),
            \Carbon\Carbon::parse($pengaturan->tanggal_tutup)->endOfDay()
        );
@endphp
<div x-data="{ yearModal: {{ $errors->has('name') || $errors->has('start_date') || $errors->has('end_date') ? 'true' : 'false' }} }">
<form method="POST" action="{{ route('admin.konfigurasi.save') }}" class="overflow-visible rounded-2xl border border-slate-200 bg-white shadow-sm">
    @csrf
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
        <div>
            <h2 class="font-black text-slate-950">Pengaturan Utama Pendaftaran</h2>
            <p class="mt-0.5 text-xs text-slate-500">Atur tahun aktif dan periode buka-tutup pendaftaran.</p>
        </div>
        <div class="flex flex-wrap gap-2"><button type="button" @click="yearModal=true" class="rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-xs font-black text-blue-800 hover:bg-blue-50">+ Tahun Ajaran</button><button class="rounded-xl bg-blue-800 px-4 py-2.5 text-xs font-black text-white shadow-sm hover:bg-blue-900">Simpan Konfigurasi</button></div>
    </div>

    <div class="border-b border-blue-100 bg-blue-50 px-5 py-3 text-xs font-semibold text-blue-800">
        Saat disimpan, Tahun Ajaran yang dipilih menjadi tahun aktif di seluruh portal. Data pendaftar lama tetap berada pada tahun asalnya; perubahan biaya hanya berlaku saat tagihan formulir baru dibuat.
    </div>
    @if($pengaturan?->status === 'aktif' && !$isWithinSchedule)
        <div class="mx-5 mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
            Status tersimpan sebagai <strong>Aktif</strong>, tetapi pendaftaran belum terbuka karena tanggal hari ini berada di luar periode buka-tutup.
        </div>
    @endif
    <div class="grid gap-4 p-5 md:grid-cols-2">
        <div>
            <x-ui-select
                name="tahun_ajaran_id"
                label="Tahun Ajaran"
                :required="true"
                placeholder="Pilih tahun ajaran"
                :selected="$pengaturan?->tahun_ajaran_id"
                :options="$tahunAjarans->map(fn($tahun) => ['value' => $tahun->id, 'label' => $tahun->name])->all()"
            />
        </div>
        <div>
            <label class="text-xs font-black text-slate-700">Tanggal Buka</label>
            <input type="date" name="tanggal_buka" value="{{ old('tanggal_buka', $pengaturan?->tanggal_buka ? \Carbon\Carbon::parse($pengaturan->tanggal_buka)->format('Y-m-d') : '') }}" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
        </div>
        <div>
            <label class="text-xs font-black text-slate-700">Tanggal Tutup</label>
            <input type="date" name="tanggal_tutup" value="{{ old('tanggal_tutup', $pengaturan?->tanggal_tutup ? \Carbon\Carbon::parse($pengaturan->tanggal_tutup)->format('Y-m-d') : '') }}" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
        </div>
        <div class="md:col-span-2">
            <x-ui-select
                name="status"
                label="Status Pendaftaran"
                :required="true"
                :selected="$pengaturan?->status ?? 'draft'"
                :options="[
                    ['value' => 'draft', 'label' => 'Persiapan', 'description' => 'Pendaftaran belum dibuka.'],
                    ['value' => 'aktif', 'label' => 'Aktif', 'description' => 'Pendaftaran dibuka sesuai periode tanggal.'],
                    ['value' => 'ditutup', 'label' => 'Ditutup', 'description' => 'Pendaftaran sedang ditutup.'],
                ]"
            />
        </div>
    </div>

    @if($errors->any())
        <div class="mx-5 mb-5 rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-600">{{ $errors->first() }}</div>
    @endif
</form>

    <div x-cloak x-show="yearModal" x-transition.opacity class="fixed inset-0 z-[2147483000] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm" @keydown.escape.window="yearModal=false">
        <section @click.outside="yearModal=false" class="w-full max-w-lg rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-100 p-5"><div><p class="text-xs font-black uppercase tracking-[.14em] text-blue-700">Konfigurasi SPMB</p><h3 class="mt-1 text-lg font-black text-slate-950">Tambah Tahun Ajaran</h3><p class="mt-1 text-xs text-slate-500">Buat pilihan tahun baru, lalu pilih sebagai tahun aktif pada konfigurasi ini.</p></div><button type="button" @click="yearModal=false" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-xl text-slate-500">×</button></div>
            <form method="POST" action="{{ route('admin.konfigurasi.tahun-ajaran.store') }}" class="space-y-4 p-5">
                @csrf
                <label class="block text-xs font-black text-slate-700">Nama Tahun Ajaran<input name="name" value="{{ old('name') }}" required placeholder="Contoh: 2027/2028" class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"></label>
                <div class="grid gap-3 sm:grid-cols-2"><label class="text-xs font-black text-slate-700">Tanggal Mulai<input type="date" name="start_date" value="{{ old('start_date') }}" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"></label><label class="text-xs font-black text-slate-700">Tanggal Selesai<input type="date" name="end_date" value="{{ old('end_date') }}" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"></label></div>
                @if($errors->has('name') || $errors->has('start_date') || $errors->has('end_date'))<p class="rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-600">{{ $errors->first() }}</p>@endif
                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" @click="yearModal=false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black text-slate-600">Batal</button><button class="rounded-xl bg-blue-800 px-4 py-2.5 text-xs font-black text-white">Tambah Tahun</button></div>
            </form>
        </section>
    </div>
</div>
@endsection
