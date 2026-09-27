@extends('layouts.admin')

@section('title', 'Master Data')
@section('page_title', 'Master Data SPMB')

@section('content')
<div class="space-y-4">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-black text-slate-950">Pusat Master Data</h2>
        <p class="mt-1 text-sm text-slate-500">Semua data referensi yang dapat berubah dikelompokkan di halaman ini.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('admin.master.sekolah.index') }}" class="rounded-xl border border-cyan-200 bg-cyan-50 p-4"><p class="text-sm font-black text-cyan-800">Asal Sekolah</p><p class="mt-1 text-xs text-cyan-700">Kelola nama sekolah dan NPSN.</p></a>
            <a href="{{ route('admin.master.agama.index') }}" class="rounded-xl border border-indigo-200 bg-indigo-50 p-4"><p class="text-sm font-black text-indigo-800">Agama</p><p class="mt-1 text-xs text-indigo-700">Pilihan agama pada biodata siswa.</p></a>
            <a href="{{ route('admin.master.pekerjaan.index') }}" class="rounded-xl border border-rose-200 bg-rose-50 p-4"><p class="text-sm font-black text-rose-800">Pekerjaan</p><p class="mt-1 text-xs text-rose-700">Pilihan pekerjaan orang tua/wali.</p></a>
            <a href="{{ route('admin.master.pendidikan.index') }}" class="rounded-xl border border-orange-200 bg-orange-50 p-4"><p class="text-sm font-black text-orange-800">Pendidikan</p><p class="mt-1 text-xs text-orange-700">Pendidikan terakhir orang tua/wali.</p></a>
            <a href="{{ route('admin.master.penghasilan.index') }}" class="rounded-xl border border-fuchsia-200 bg-fuchsia-50 p-4"><p class="text-sm font-black text-fuchsia-800">Penghasilan</p><p class="mt-1 text-xs text-fuchsia-700">Rentang penghasilan orang tua/wali.</p></a>
            <a href="{{ route('admin.master.jalur-pendaftaran.index') }}" class="rounded-xl border border-indigo-200 bg-indigo-50 p-4"><p class="text-sm font-black text-indigo-800">Jalur Pendaftaran</p><p class="mt-1 text-xs text-indigo-700">Pilihan jalur masuk siswa.</p></a>
            <a href="{{ route('admin.jurusan.index') }}" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4"><p class="text-sm font-black text-emerald-800">Jurusan</p><p class="mt-1 text-xs text-emerald-700">Nama, kode, kuota, dan status.</p></a>
            <a href="{{ route('admin.gelombang.index') }}" class="rounded-xl border border-sky-200 bg-sky-50 p-4"><p class="text-sm font-black text-sky-800">Gelombang</p><p class="mt-1 text-xs text-sky-700">Periode dan gelombang aktif.</p></a>
            <a href="#tahun-ajaran" class="rounded-xl border border-violet-200 bg-violet-50 p-4"><p class="text-sm font-black text-violet-800">Tahun Ajaran</p><p class="mt-1 text-xs text-violet-700">Periode akademik sekolah.</p></a>
            <a href="#informasi-tes" class="rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="text-sm font-black text-amber-800">Informasi Tes</p><p class="mt-1 text-xs text-amber-700">Jadwal dan keterangan.</p></a>
        </div>
    </section>

    <section id="tahun-ajaran" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between"><div><h3 class="font-black text-slate-950">Tahun Ajaran</h3><p class="text-xs text-slate-500">Daftar tahun ajaran yang tersimpan.</p></div><span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-black text-violet-700">{{ $tahunAjarans->count() }} data</span></div>
        <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($tahunAjarans as $tahun)
                <div class="rounded-xl border border-slate-200 p-4"><div class="flex items-center justify-between"><strong class="text-sm text-slate-900">{{ $tahun->name }}</strong><span class="rounded-full px-2 py-1 text-[10px] font-black {{ $tahun->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $tahun->is_active ? 'Aktif' : 'Tidak aktif' }}</span></div><p class="mt-1 text-xs text-slate-500">{{ $tahun->start_date ?: '-' }} — {{ $tahun->end_date ?: '-' }}</p></div>
            @empty <p class="text-sm text-slate-500">Belum ada tahun ajaran.</p> @endforelse
        </div>
    </section>

    <section id="informasi-tes" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between"><div><h3 class="font-black text-slate-950">Informasi & Jadwal Tes</h3><p class="text-xs text-slate-500">Jadwal kegiatan SPMB yang tampil kepada pengguna.</p></div><span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700">{{ $jadwalTes->count() }} jadwal</span></div>
        <div class="mt-4 overflow-x-auto"><table class="w-full min-w-[520px] text-left text-sm"><thead class="bg-slate-50 text-[10px] uppercase text-slate-500"><tr><th class="p-3">Kegiatan</th><th class="p-3">Mulai</th><th class="p-3">Selesai</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($jadwalTes as $jadwal)<tr><td class="p-3 font-bold text-slate-900">{{ $jadwal->kegiatan }}</td><td class="p-3 text-slate-600">{{ $jadwal->tanggal_mulai ?: '-' }}</td><td class="p-3 text-slate-600">{{ $jadwal->tanggal_selesai ?: '-' }}</td></tr>@empty<tr><td colspan="3" class="p-6 text-center text-slate-500">Belum ada jadwal tes.</td></tr>@endforelse</tbody></table></div>
    </section>

    <section id="konfigurasi-spmb" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="font-black text-slate-950">Konfigurasi SPMB</h3><p class="text-xs text-slate-500">Ringkasan konfigurasi pendaftaran yang sedang digunakan.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-slate-50 p-4"><p class="text-[10px] font-black uppercase text-slate-400">Tahun Ajaran</p><p class="mt-1 text-sm font-bold text-slate-900">{{ $pengaturan?->tahunAjaran?->name ?? $activeAcademicYear?->name ?? '-' }}</p></div>
            <div class="rounded-xl bg-slate-50 p-4"><p class="text-[10px] font-black uppercase text-slate-400">Tanggal Buka</p><p class="mt-1 text-sm font-bold text-slate-900">{{ $pengaturan?->tanggal_buka ?? '-' }}</p></div>
            <div class="rounded-xl bg-slate-50 p-4"><p class="text-[10px] font-black uppercase text-slate-400">Tanggal Tutup</p><p class="mt-1 text-sm font-bold text-slate-900">{{ $pengaturan?->tanggal_tutup ?? '-' }}</p></div>
            <div class="rounded-xl bg-slate-50 p-4"><p class="text-[10px] font-black uppercase text-slate-400">Status</p><p class="mt-1 text-sm font-bold capitalize text-emerald-700">{{ $pengaturan?->status ?? 'belum diatur' }}</p></div>
        </div>
    </section>
</div>
@endsection
