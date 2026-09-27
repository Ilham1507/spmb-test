@extends('layouts.admin')

@section('title', 'Kelola Formulir')
@section('page_title', 'Kelola Formulir')
@section('page_description', 'Pilih data yang tampil saat siswa mengisi formulir pendaftaran.')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    .form-field-row { align-items: center; display: grid !important; gap: 8px !important; grid-template-columns: minmax(0, 1fr) 38px 38px 38px 38px !important; min-height: 46px; }
    .form-field-row input { margin: 0 !important; }
    .form-field-card-head { align-items: center; display: grid !important; gap: 8px !important; grid-template-columns: minmax(0, 1fr) 38px 38px 38px 38px !important; min-height: 64px; position: relative; }
    .form-field-card-head .form-field-count { position: absolute; right: 12px; top: 10px; }
    .form-field-label { align-self: end; padding-bottom: 2px; text-align: center; }
</style>

<div x-data="{ open: false, mode: 'add', editAction: '', editLabel: '', editGroup: '' }" @open-edit-field.window="mode = 'edit'; editAction = $event.detail.action; editLabel = $event.detail.label; editGroup = $event.detail.group; open = true">
    <section class="admin-card !overflow-visible bg-white">
        <div class="flex items-center justify-between gap-3 p-4">
            <div>
                <h2 class="text-base font-black text-slate-950">Field Tambahan</h2>
                <p class="mt-1 text-xs text-slate-500">Tambah data khusus sekolah bila diperlukan.</p>
            </div>
            <button type="button" @click="mode = 'add'; editLabel = ''; editGroup = '{{ array_key_first($groups) }}'; open = true" class="btn-primary whitespace-nowrap">+ Tambah Field</button>
        </div>
        @if($customFields)
            <div class="border-t border-slate-100 px-4 py-2">
                @foreach($customFields as $field)
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 py-2 last:border-0">
                        <div class="min-w-0"><p class="truncate text-sm font-bold text-slate-700">{{ $field['label'] }}</p><p class="text-[11px] text-slate-400">Data {{ $field['group'] }}</p></div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button type="button" @click="mode = 'edit'; editAction = '{{ route('admin.formulir.custom.update', $field['key']) }}'; editLabel = @js($field['label']); editGroup = @js($field['group']); open = true" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Edit</button>
                            <form method="POST" action="{{ route('admin.formulir.custom.destroy', $field['key']) }}" onsubmit="return confirm('Hapus field ini?')">@csrf @method('DELETE')<button type="submit" class="inline-flex rounded-lg border border-rose-200 px-3 py-1.5 text-rose-700 hover:bg-rose-50" aria-label="Hapus field"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"/></svg></button></form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        <div x-show="open" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/40 p-4" @keydown.escape.window="open = false">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl" @click.outside="open = false">
                <div class="flex items-center justify-between gap-3"><h3 class="text-lg font-black text-slate-950" x-text="mode === 'add' ? 'Tambah Field' : 'Edit Field'"></h3><button type="button" @click="open = false" class="text-xl text-slate-400">&times;</button></div>
                <form method="POST" action="{{ route('admin.formulir.custom.store') }}" class="mt-5 space-y-4" x-bind:action="mode === 'add' ? '{{ route('admin.formulir.custom.store') }}' : editAction">
                    @csrf <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>
                    <input name="label" required x-model="editLabel" placeholder="Nama field, contoh: Nomor KIP" class="admin-input">
                    <select name="group" required x-model="editGroup" class="admin-input">@foreach(array_keys($groups) as $group)<option value="{{ $group }}">Data {{ $group }}</option>@endforeach</select>
                    <div class="flex justify-end gap-2"><button type="button" @click="open = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600">Batal</button><button type="submit" class="btn-primary" x-text="mode === 'add' ? 'Tambah' : 'Simpan Perubahan'"></button></div>
                </form>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ route('admin.formulir.save') }}" x-data="{ enabled: @js($enabledFields), required: @js($requiredFields) }" class="mt-4 space-y-4">
        @csrf
        <section class="admin-card bg-white">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5"><div><h2 class="text-lg font-black text-slate-950">Field Formulir Pendaftaran</h2><p class="mt-1 text-sm text-slate-500">Tentukan field yang tampil dan wajib diisi siswa.</p></div><div class="flex flex-wrap gap-2 text-xs font-bold text-slate-500"><button type="button" @click="enabled = @js(array_keys(collect($groups)->mapWithKeys(fn($fields) => $fields)->all()))" class="rounded-xl border border-slate-200 px-3 py-2 hover:bg-slate-50">Aktifkan Semua</button><button type="button" @click="enabled = []" class="rounded-xl border border-slate-200 px-3 py-2 hover:bg-slate-50">Matikan Semua</button></div></div>
            <div class="grid grid-cols-1 items-start gap-3 p-3 sm:gap-4 sm:p-5 lg:grid-cols-2 xl:grid-cols-3">
                @foreach($groups as $group => $fields)
                    <article class="min-w-0 overflow-hidden rounded-2xl border border-slate-200"><div class="form-field-card-head border-b border-slate-100 bg-slate-50 px-3 py-3 sm:px-4"><h3 class="min-w-0 truncate font-black text-slate-800">Data {{ $group }}{{ $group === 'Wali' ? ' (Opsional)' : '' }}</h3><span class="form-field-label text-[9px] font-black uppercase tracking-wide text-slate-400">Tampil</span><span class="form-field-label text-[9px] font-black uppercase tracking-wide text-slate-400">Wajib</span><span class="form-field-label text-[9px] font-black uppercase tracking-wide text-slate-400">Edit</span><span class="form-field-label text-[9px] font-black uppercase tracking-wide text-slate-400">Hapus</span><span class="form-field-count rounded-full bg-white px-2 py-1 text-[10px] font-black text-slate-500">{{ count($fields) }}</span></div><div class="divide-y divide-slate-100">
                        @foreach($fields as $key => $label)
                            <label class="form-field-row cursor-pointer px-3 py-1.5 text-sm transition hover:bg-emerald-50/60 sm:px-4"><span class="min-w-0 whitespace-normal break-words font-semibold leading-tight text-slate-700" title="{{ $label }}">{{ $label }}</span><input aria-label="Tampilkan {{ $label }}" type="checkbox" name="fields[]" value="{{ $key }}" x-model="enabled" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"><input aria-label="Wajib {{ $label }}" type="checkbox" name="required_fields[]" value="{{ $key }}" x-model="required" :disabled="!enabled.includes('{{ $key }}')" class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500 disabled:opacity-30"><button type="button" aria-label="Edit {{ $label }}" title="Edit" @click="$dispatch('open-edit-field', { action: '{{ route('admin.formulir.custom.update', $key) }}', label: @js($label), group: @js($group) })" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-sky-100 text-base font-black text-sky-600 hover:bg-sky-50">✎</button><button type="submit" form="delete-field-{{ $key }}" aria-label="Hapus {{ $label }}" title="Hapus" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-rose-100 text-rose-600 hover:bg-rose-50"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"/></svg></button></label>
                        @endforeach
                    </div></article>
                @endforeach
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5"><p class="text-xs font-semibold text-slate-500">Perubahan berlaku saat siswa memuat ulang formulir.</p><button type="submit" class="btn-primary">Simpan Pengaturan Formulir</button></div>
        </section>
    </form>
    @foreach($groups as $fields)
        @foreach($fields as $key => $label)
            <form id="delete-field-{{ $key }}" method="POST" action="{{ route('admin.formulir.custom.destroy', $key) }}" class="hidden" onsubmit="return confirm('Hapus field {{ $label }}?')">@csrf @method('DELETE')</form>
        @endforeach
    @endforeach
</div>
@endsection
