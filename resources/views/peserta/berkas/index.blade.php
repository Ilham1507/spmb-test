@extends('layouts.peserta')

@section('title', 'Upload Berkas')
@section('page_title', 'Unggah Dokumen Pendukung')

@section('content')
<div class="max-w-2xl bg-white p-8 rounded-3xl border border-slate-200/60 shadow-sm space-y-6">
    <h3 class="font-bold text-slate-900 text-lg border-b border-slate-100 pb-4">Unggah Berkas Pendaftaran</h3>
    <form action="#" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <div class="space-y-1">
            <label class="block text-xs font-semibold text-slate-500">Scan Kartu Keluarga (KK)</label>
            <input type="file" name="kk" class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100">
        </div>
        <div class="space-y-1">
            <label class="block text-xs font-semibold text-slate-500">Scan Akta Kelahiran</label>
            <input type="file" name="akta" class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100">
        </div>
        <div class="pt-4">
            <button type="submit" class="btn-primary">
                Unggah Berkas
            </button>
        </div>
    </form>
</div>
@endsection
