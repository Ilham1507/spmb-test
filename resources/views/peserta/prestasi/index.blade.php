@extends('layouts.peserta')

@section('title', 'Data Prestasi')
@section('page_title', 'Prestasi Calon Siswa (Opsional)')

@section('content')
<div class="max-w-2xl bg-white p-8 rounded-3xl border border-slate-200/60 shadow-sm space-y-6">
    <div class="border-b border-slate-100 pb-4 flex justify-between items-center">
        <h3 class="font-bold text-slate-900 text-lg">Prestasi Akademik / Non-Akademik</h3>
        <button class="btn-primary !min-h-0 !px-3 !py-1.5 !text-xs">Tambah Prestasi</button>
    </div>
    
    <div class="text-center py-8 text-slate-400 space-y-2">
        <p class="text-sm">Belum ada data prestasi yang diinputkan.</p>
        <p class="text-xs">Jika Anda memiliki sertifikat lomba, kejuaraan, atau tahfidz, silakan tambahkan di sini.</p>
    </div>
</div>
@endsection
