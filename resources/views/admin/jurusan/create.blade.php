@extends('layouts.admin')

@section('title', 'Tambah Jurusan')
@section('page_title', 'Tambah Jurusan Baru')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center gap-4">
            <a href="{{ route('admin.jurusan.index') }}" class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-500 hover:bg-slate-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <h3 class="font-bold text-slate-900 text-lg">Form Tambah Jurusan</h3>
        </div>
        
        <form action="{{ route('admin.jurusan.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label for="quota" class="text-sm font-semibold text-slate-700">Kapasitas Kuota <span class="text-rose-500">*</span></label>
                    <input type="number" name="quota" id="quota" value="{{ old('quota', 0) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" min="0" required>
                    @error('quota') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-2">
                <label for="name" class="text-sm font-semibold text-slate-700">Nama Jurusan <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" placeholder="Contoh: Rekayasa Perangkat Lunak" required>
                @error('name') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="space-y-2"><label for="logo" class="text-sm font-semibold text-slate-700">Logo Jurusan</label><input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/webp" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm"></div>
            </div>

            <div class="space-y-2">
                <x-ui-select
                    name="status"
                    label="Status"
                    :required="true"
                    :selected="old('status', 'aktif')"
                    :options="[
                        ['value' => 'aktif', 'label' => 'Aktif'],
                        ['value' => 'nonaktif', 'label' => 'Tidak Aktif'],
                    ]"
                />
                @error('status') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <a href="{{ route('admin.jurusan.index') }}" class="px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Batal</a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm px-6 py-2.5 rounded-xl transition-colors shadow-sm shadow-indigo-200">Simpan Jurusan</button>
            </div>
        </form>
    </div>
</div>
@endsection
