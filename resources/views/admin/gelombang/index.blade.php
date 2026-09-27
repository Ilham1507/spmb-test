@extends('layouts.admin')

@section('title', 'Gelombang')
@section('page_title', 'Kelola Gelombang')

@section('content')
<div x-data="{ addOpen: {{ $errors->any() ? 'true' : 'false' }}, editId: null }">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
            <div>
                <div class="flex items-center gap-2"><h2 class="font-black text-slate-950">Daftar Gelombang</h2><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600">{{ $gelombangs->count() }} data</span></div>
                <p class="mt-0.5 text-xs text-slate-500">Tanggal, kuota, dan status diatur manual oleh admin.</p>
            </div>
            <button type="button" @click="addOpen=true" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-sm hover:bg-emerald-700">+ Tambah Gelombang</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400">
                    <tr><th class="px-4 py-3">Gelombang</th><th class="px-4 py-3">Tahun Ajaran</th><th class="px-4 py-3">Periode</th><th class="px-4 py-3">Kuota</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($gelombangs as $gelombang)
                        <tr class="{{ $gelombang->status === 'aktif' ? 'bg-emerald-50/60' : 'bg-white' }}">
                            <td class="px-4 py-3 font-black text-slate-950">{{ $gelombang->name }}</td>
                            <td class="px-4 py-3 font-bold text-slate-600">{{ $gelombang->tahunAjaran?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600"><span class="font-bold">{{ \Carbon\Carbon::parse($gelombang->start_date)->translatedFormat('d M Y') }}</span><span class="mx-2 text-slate-300">-</span><span class="font-bold">{{ \Carbon\Carbon::parse($gelombang->end_date)->translatedFormat('d M Y') }}</span></td>
                            <td class="px-4 py-3 font-bold text-slate-700">{{ $gelombang->quota }} siswa</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $gelombang->status === 'aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $gelombang->status === 'aktif' ? 'Aktif' : 'Tidak Aktif' }}</span></td>
                            <td class="px-4 py-3"><div class="flex justify-end gap-2"><button type="button" @click="editId={{ $gelombang->id }}" class="rounded-lg bg-sky-100 px-3 py-2 text-xs font-black text-sky-700">Edit</button><form method="POST" action="{{ route('admin.gelombang.destroy', $gelombang) }}" onsubmit="return confirm('Hapus gelombang ini?')">@csrf @method('DELETE')<button class="rounded-lg bg-rose-100 px-3 py-2 text-xs font-black text-rose-700">Hapus</button></form></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-10 text-center text-sm text-slate-500">Belum ada gelombang pendaftaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div x-cloak x-show="addOpen" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm">
        <div @click.outside="addOpen=false" class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-100 p-5"><div><h3 class="text-lg font-black text-slate-950">Tambah Gelombang</h3><p class="mt-1 text-xs text-slate-500">Semua data diisi manual.</p></div><button type="button" @click="addOpen=false" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">&times;</button></div>
            <form method="POST" action="{{ route('admin.gelombang.store') }}" class="space-y-4 p-5">@csrf
                @include('admin.gelombang.partials.form', ['gelombang' => null, 'jurusans' => $jurusans])
                @if($errors->any())<p class="rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-600">{{ $errors->first() }}</p>@endif
                <div class="grid grid-cols-2 gap-2"><button type="button" @click="addOpen=false" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600">Batal</button><button class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Simpan</button></div>
            </form>
        </div>
    </div>

    @foreach($gelombangs as $gelombang)
        <div x-cloak x-show="editId==={{ $gelombang->id }}" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm">
            <div @click.outside="editId=null" class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-100 p-5"><div><h3 class="text-lg font-black text-slate-950">Edit Gelombang</h3><p class="mt-1 text-xs text-slate-500">Ubah periode, kuota, atau status.</p></div><button type="button" @click="editId=null" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">&times;</button></div>
                <form method="POST" action="{{ route('admin.gelombang.update', $gelombang) }}" class="space-y-4 p-5">@csrf @method('PUT')
                    @include('admin.gelombang.partials.form', ['gelombang' => $gelombang, 'jurusans' => $jurusans])
                    <div class="grid grid-cols-2 gap-2"><button type="button" @click="editId=null" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600">Batal</button><button class="rounded-xl bg-sky-600 px-4 py-3 text-sm font-black text-white">Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
