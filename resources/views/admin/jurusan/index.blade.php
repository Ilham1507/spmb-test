@extends('layouts.admin')

@section('title', 'Data Jurusan')
@section('page_title', 'Kelola Jurusan')

@section('content')
<div x-data="{ addOpen: {{ $errors->any() ? 'true' : 'false' }}, editId: null }">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-black text-slate-950">Daftar Jurusan</h2>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600">{{ $jurusans->count() }} data</span>
                </div>
                <p class="mt-0.5 text-xs text-slate-500">Nama, kuota, logo, dan status jurusan diatur di sini. Nominal dikelola di Keuangan.</p>
            </div>
            <button type="button" @click="addOpen=true" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-sm hover:bg-emerald-700">+ Tambah Jurusan</button>
        </div>

        @if(session('success'))
            <div class="border-b border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full min-w-[780px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Jurusan</th>
                        <th class="px-4 py-3">Kuota</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($jurusans as $jurusan)
                        <tr class="{{ $jurusan->status === 'aktif' ? 'bg-emerald-50/50' : 'bg-white' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white">
                                        @if($jurusan->logo_path)
                                            <img src="{{ asset($jurusan->logo_path) }}" class="h-9 w-9 object-contain" alt="Logo {{ $jurusan->name }}">
                                        @else
                                            <span class="text-xs font-black text-slate-400">{{ strtoupper(substr($jurusan->name, 0, 2)) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-950">{{ $jurusan->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-bold text-slate-700">{{ $jurusan->quota }} siswa</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $jurusan->status === 'aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $jurusan->status === 'aktif' ? 'Aktif' : 'Tidak Aktif' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="editId={{ $jurusan->id }}" class="rounded-lg bg-sky-100 px-3 py-2 text-xs font-black text-sky-700">Edit</button>
                                    <form method="POST" action="{{ route('admin.jurusan.destroy', $jurusan) }}" onsubmit="return confirm('Hapus hanya jika jurusan belum pernah dipilih siswa. Lanjutkan?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg bg-rose-100 px-3 py-2 text-xs font-black text-rose-700">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-10 text-center text-sm text-slate-500">Belum ada data jurusan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-per-page-pagination :paginator="$jurusans" />
    </section>

    <div x-cloak x-show="addOpen" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm" @keydown.escape.window="addOpen=false">
        <div @click.outside="addOpen=false" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-100 p-5">
                <div>
                    <h3 class="text-lg font-black text-slate-950">Tambah Jurusan</h3>
                    <p class="mt-1 text-xs text-slate-500">Isi data jurusan sesuai data sekolah.</p>
                </div>
                <button type="button" @click="addOpen=false" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.jurusan.store') }}" enctype="multipart/form-data" class="space-y-4 p-5">
                @csrf
                @include('admin.jurusan.partials.form', ['jurusan' => null])
                @if($errors->any())<p class="rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-600">{{ $errors->first() }}</p>@endif
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="addOpen=false" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600">Batal</button>
                    <button class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($jurusans as $jurusan)
        <div x-cloak x-show="editId==={{ $jurusan->id }}" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm" @keydown.escape.window="editId=null">
            <div @click.outside="editId=null" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-100 p-5">
                    <div>
                        <h3 class="text-lg font-black text-slate-950">Edit Jurusan</h3>
                        <p class="mt-1 text-xs text-slate-500">Ubah nama, kuota, logo, atau status jurusan.</p>
                    </div>
                    <button type="button" @click="editId=null" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.jurusan.update', $jurusan) }}" enctype="multipart/form-data" class="space-y-4 p-5">
                    @csrf
                    @method('PUT')
                    @include('admin.jurusan.partials.form', ['jurusan' => $jurusan])
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="editId=null" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600">Batal</button>
                        <button class="rounded-xl bg-sky-600 px-4 py-3 text-sm font-black text-white">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
