@extends('layouts.admin')
@section('title', $pageLabel ?? 'Master Dropdown')
@section('page_title', 'Master Data ' . ($pageLabel ?? 'Dropdown Formulir'))
@section('content')
<div x-data="{ open: false, table: '', label: '', column: '', id: '', value: '' }" class="space-y-4">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-black text-slate-950">Master {{ $pageLabel ?? 'Dropdown Formulir' }}</h2>
        <p class="mt-1 text-sm text-slate-500">Kelola data {{ strtolower($pageLabel ?? 'dropdown') }} yang dipakai formulir siswa.</p>
    </section>
    <div class="grid gap-4">
        @foreach($tables as $table => $label)
            @php $column = $table === 'jalur_pendaftaran' ? 'name' : 'nama'; @endphp
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-black text-slate-950">{{ $label }}</h3>
                    <button type="button" class="btn-primary px-3 py-2 text-xs"
                        @click="open = true; table = '{{ $table }}'; label = '{{ $label }}'; column = '{{ $column }}'; id = ''; value = ''">+ Tambah</button>
                </div>
                <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($data[$table] as $item)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5 text-sm">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-700">{{ $item->{$column} }}</p>
                                @if($table === 'jalur_pendaftaran')
                                    <span class="text-xs {{ $item->status === 'nonaktif' ? 'text-slate-400' : 'text-emerald-600' }}">{{ ucfirst($item->status) }}</span>
                                @endif
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <button type="button" class="text-xs font-bold text-sky-700"
                                    @click="open = true; table = '{{ $table }}'; label = '{{ $label }}'; column = '{{ $column }}'; id = '{{ $item->id }}'; value = @js($item->{$column})">Edit</button>
                                @if($table === 'jalur_pendaftaran' && $item->status === 'nonaktif')
                                    <span class="text-xs text-slate-400">Nonaktif</span>
                                @else
                                    <form method="POST" action="{{ route('admin.master.dropdown.destroy', [$table, $item->id]) }}" onsubmit="return confirm('Hapus pilihan ini? Data pendaftar yang sudah ada tetap tersimpan.')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs font-bold text-rose-600">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">Belum ada data.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @keydown.escape.window="open = false">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" @click.outside="open = false">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-950" x-text="id ? 'Edit ' + label : 'Tambah ' + label"></h3>
                    <p class="mt-1 text-xs text-slate-500">Perubahan langsung tersedia di formulir siswa.</p>
                </div>
                <button type="button" class="rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-100" @click="open = false">✕</button>
            </div>
            <form method="POST" class="mt-5 space-y-4" :action="id ? '/admin/master/dropdown/' + table + '/' + id : '/admin/master/dropdown/' + table">
                @csrf
                <input type="hidden" name="_method" :value="id ? 'PUT' : 'POST'">
                <label class="block text-sm font-bold text-slate-700">Nama pilihan
                    <input :name="column" x-model="value" required maxlength="150" class="admin-input mt-2 w-full" placeholder="Masukkan nama pilihan">
                </label>
                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" class="btn-secondary" @click="open = false">Batal</button>
                    <button class="btn-primary" x-text="id ? 'Simpan Perubahan' : 'Tambah Data'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
