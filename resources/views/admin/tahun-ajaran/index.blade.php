@extends('layouts.admin')

@section('title','Tahun Ajaran')
@section('page_title','Kelola Tahun Ajaran')

@section('content')
<div x-data="{ addOpen: {{ $errors->any() ? 'true' : 'false' }}, editId: null, activateId: null, deleteBlockedId: null }">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-black text-slate-950">Daftar Tahun Ajaran</h2>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600">{{ $tahunAjarans->count() }} data</span>
                </div>
                <p class="mt-0.5 text-xs text-slate-500">Periode diisi manual sesuai aturan sekolah.</p>
            </div>
            <button type="button" @click="addOpen=true" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-sm hover:bg-emerald-700">+ Tambah Tahun Ajaran</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Tahun Ajaran</th>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tahunAjarans as $tahun)
                        <tr class="{{ $tahun->is_active ? 'bg-emerald-50/60' : 'bg-white' }}">
                            <td class="px-4 py-3">
                                <p class="font-black text-slate-900">{{ $tahun->name }}</p>
                                @if($tahun->is_active)<p class="mt-0.5 text-[10px] font-bold text-emerald-600">Sedang digunakan sistem</p>@endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <span class="font-bold">{{ \Carbon\Carbon::parse($tahun->start_date)->translatedFormat('d M Y') }}</span>
                                <span class="mx-2 text-slate-300">-</span>
                                <span class="font-bold">{{ \Carbon\Carbon::parse($tahun->end_date)->translatedFormat('d M Y') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $tahun->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $tahun->is_active ? 'Aktif' : 'Tidak Aktif' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @unless($tahun->is_active)
                                        <button type="button" @click="activateId={{ $tahun->id }}" class="rounded-lg bg-emerald-100 px-3 py-2 text-xs font-black text-emerald-700">Aktifkan</button>
                                    @endunless
                                    <button type="button" @click="editId={{ $tahun->id }}" class="rounded-lg bg-sky-100 px-3 py-2 text-xs font-black text-sky-700">Edit</button>
                                    @if($tahun->is_active)
                                        <button type="button" @click="deleteBlockedId={{ $tahun->id }}" class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-black text-rose-600">Hapus</button>
                                    @else
                                        <form method="POST" action="{{ route('admin.tahun-ajaran.destroy',$tahun) }}" onsubmit="return confirm('Hapus tahun ajaran ini?')">
                                            @csrf @method('DELETE')
                                            <button class="rounded-lg bg-rose-100 px-3 py-2 text-xs font-black text-rose-700">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-10 text-center text-sm text-slate-500">Belum ada tahun ajaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div x-cloak x-show="addOpen" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm" @keydown.escape.window="addOpen=false">
        <div @click.outside="addOpen=false" class="w-full max-w-lg rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-100 p-5">
                <div>
                    <h3 class="text-lg font-black text-slate-950">Tambah Tahun Ajaran</h3>
                    <p class="mt-1 text-xs text-slate-500">Isi nama dan periode sesuai kebijakan sekolah.</p>
                </div>
                <button type="button" @click="addOpen=false" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.tahun-ajaran.store') }}" class="space-y-4 p-5">
                @csrf
                <div>
                    <label class="text-xs font-black text-slate-700">Nama Tahun Ajaran</label>
                    <input name="name" value="{{ old('name') }}" placeholder="Contoh: 2026/2027" class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-black text-slate-700">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}" class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                    </div>
                    <div>
                        <label class="text-xs font-black text-slate-700">Tanggal Selesai</label>
                        <input type="date" name="end_date" value="{{ old('end_date') }}" class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                    </div>
                </div>
                <label class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm font-bold"><input type="checkbox" name="is_active" value="1" @checked(old('is_active')) class="rounded border-slate-300 text-emerald-600"> Langsung jadikan tahun ajaran aktif</label>
                @if($errors->any())<p class="rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-600">{{ $errors->first() }}</p>@endif
                <div class="grid grid-cols-2 gap-2"><button type="button" @click="addOpen=false" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600">Batal</button><button class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Simpan</button></div>
            </form>
        </div>
    </div>

    @foreach($tahunAjarans as $tahun)
        <div x-cloak x-show="editId==={{ $tahun->id }}" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm" @keydown.escape.window="editId=null">
            <div @click.outside="editId=null" class="w-full max-w-lg rounded-3xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-100 p-5">
                    <div><h3 class="text-lg font-black text-slate-950">Edit Tahun Ajaran</h3><p class="mt-1 text-xs text-slate-500">Ubah data secara manual.</p></div>
                    <button @click="editId=null" type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.tahun-ajaran.update',$tahun) }}" class="space-y-4 p-5">
                    @csrf @method('PUT')
                    <div>
                        <label class="text-xs font-black text-slate-700">Nama Tahun Ajaran</label>
                        <input name="name" value="{{ old('name', $tahun->name) }}" class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100">
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-black text-slate-700">Tanggal Mulai</label>
                            <input type="date" name="start_date" value="{{ old('start_date', \Carbon\Carbon::parse($tahun->start_date)->format('Y-m-d')) }}" class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100">
                        </div>
                        <div>
                            <label class="text-xs font-black text-slate-700">Tanggal Selesai</label>
                            <input type="date" name="end_date" value="{{ old('end_date', \Carbon\Carbon::parse($tahun->end_date)->format('Y-m-d')) }}" class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100">
                        </div>
                    </div>
                    <label class="flex cursor-pointer items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <span><strong class="block text-sm text-slate-900">Status aktif</strong><small class="text-xs text-slate-500">Hanya satu tahun ajaran yang dapat aktif.</small></span>
                        <span class="relative inline-flex"><input type="checkbox" name="is_active" value="1" @checked($tahun->is_active) class="peer sr-only"><span class="h-7 w-12 rounded-full bg-slate-300 transition peer-checked:bg-emerald-500"></span><span class="absolute left-1 top-1 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span></span>
                    </label>
                    <div class="grid grid-cols-2 gap-2"><button type="button" @click="editId=null" class="rounded-xl border px-4 py-3 text-sm font-black text-slate-600">Batal</button><button class="rounded-xl bg-sky-600 px-4 py-3 text-sm font-black text-white">Simpan Perubahan</button></div>
                </form>
            </div>
        </div>

        @unless($tahun->is_active)
            <div x-cloak x-show="activateId==={{ $tahun->id }}" x-transition.opacity class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm" @keydown.escape.window="activateId=null">
                <div @click.outside="activateId=null" class="w-full max-w-md rounded-3xl bg-white p-5 shadow-2xl">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01 M10.3 4.2 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0Z"/></svg></div>
                    <h3 class="mt-4 text-lg font-black text-slate-950">Aktifkan {{ $tahun->name }}?</h3>
                    <p class="mt-2 text-sm text-slate-600">Tahun ajaran aktif saat ini akan dinonaktifkan.</p>
                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <button type="button" @click="activateId=null; editId={{ $tahun->id }}" class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-black text-sky-700">Edit Dahulu</button>
                        <form method="POST" action="{{ route('admin.tahun-ajaran.update',$tahun) }}">@csrf @method('PUT')<input type="hidden" name="name" value="{{ $tahun->name }}"><input type="hidden" name="start_date" value="{{ \Carbon\Carbon::parse($tahun->start_date)->format('Y-m-d') }}"><input type="hidden" name="end_date" value="{{ \Carbon\Carbon::parse($tahun->end_date)->format('Y-m-d') }}"><input type="hidden" name="is_active" value="1"><button class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Ya, Aktifkan</button></form>
                    </div>
                </div>
            </div>
        @endunless

        @if($tahun->is_active)
            <div x-cloak x-show="deleteBlockedId==={{ $tahun->id }}" x-transition.opacity class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm" @keydown.escape.window="deleteBlockedId=null">
                <div @click.outside="deleteBlockedId=null" class="w-full max-w-sm rounded-3xl bg-white p-5 shadow-2xl">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-100 text-rose-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v5m0 4h.01 M10 3h4l1 2h5v2H4V5h5z M6 7l1 14h10l1-14"/></svg></div>
                    <h3 class="mt-4 text-lg font-black text-slate-950">Belum bisa dihapus</h3>
                    <p class="mt-1 text-sm text-slate-600">Ubah status {{ $tahun->name }} menjadi tidak aktif terlebih dahulu.</p>
                    <div class="mt-5 grid grid-cols-2 gap-2"><button type="button" @click="deleteBlockedId=null" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600">Tutup</button><button type="button" @click="deleteBlockedId=null; editId={{ $tahun->id }}" class="rounded-xl bg-sky-600 px-4 py-3 text-sm font-black text-white">Buka Edit</button></div>
                </div>
            </div>
        @endif
    @endforeach
</div>
@endsection
