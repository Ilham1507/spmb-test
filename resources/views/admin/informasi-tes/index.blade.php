@extends('layouts.admin')

@section('title', 'Informasi Tes')
@section('page_title', 'Kelola Informasi Tes')

@section('content')
<style>
    .test-schedule-overlay { position:fixed!important; inset:0!important; z-index:100000!important; display:flex; align-items:center; justify-content:center; padding:20px; background:rgba(15,23,42,.55); backdrop-filter:blur(4px); }
    .test-schedule-dialog { display:flex; flex-direction:column; width:100%; max-width:min(720px,calc(100vw - 40px)); max-height:calc(100dvh - 40px); overflow:hidden; border-radius:24px; background:#fff; box-shadow:0 24px 80px #0f172a55; }
    .test-schedule-dialog > div { flex-shrink:0; }
    .test-schedule-dialog > form { display:flex; flex-direction:column; min-height:0; overflow:hidden; padding:0!important; gap:0!important; }
    .test-schedule-fields { padding:24px; overflow-y:auto; min-height:0; }
    .test-schedule-footer { flex-shrink:0; margin:0!important; padding:16px 24px; border-top:1px solid #e2e8f0; background:#fff; }
    .test-schedule-fields input, .test-schedule-fields textarea { min-width:0; max-width:100%; }
    @media(max-width:640px) { .test-schedule-overlay{padding:12px;width:100vw!important;right:auto!important} .test-schedule-dialog{max-width:calc(100vw - 24px);max-height:calc(100dvh - 24px);border-radius:20px} .test-schedule-fields{padding:18px} .test-schedule-footer{padding:14px 18px} }
</style>
<div x-data="{ addOpen: {{ $errors->any() && !$errors->has('items') && !$errors->has('items.*') ? 'true' : 'false' }}, editId: null }">
    <details class="mb-5 rounded-2xl border border-teal-200 bg-white p-5 shadow-sm" {{ $errors->has('items') || $errors->has('items.*') ? 'open' : '' }}>
        <summary class="cursor-pointer font-black text-teal-800">Persiapan yang ditampilkan kepada siswa <span class="ml-2 text-xs font-medium text-slate-500">Klik untuk mengatur</span></summary>
        <p class="mt-3 text-sm text-slate-600">Tulis satu persiapan per kartu. Judul dan penjelasan tampil otomatis di halaman Tes SPMB siswa.</p>
        @if($errors->has('items') || $errors->has('items.*'))
            <p class="mt-3 text-sm font-bold text-rose-600">{{ $errors->first() }}</p>
        @endif
        @php
            $preparationEditorItems = collect(old('items', $testPreparation))->values()->map(fn ($item, $index) => array_merge($item, ['icon' => \App\Support\TestPreparationIcons::resolve($item['icon'] ?? null, $index)]))->all();
        @endphp
        <form method="POST" action="{{ route('admin.informasi-tes.preparation.save') }}" class="mt-4" x-data="{ items: @js($preparationEditorItems) }">@csrf
            <div class="grid gap-4 md:grid-cols-2">
                <template x-for="(item, index) in items" :key="index">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-3 flex items-center justify-between"><span class="text-xs font-bold text-teal-700" x-text="'Persiapan ' + (index + 1)"></span><button type="button" :disabled="items.length === 1" @click="items.splice(index, 1)" class="text-xs font-bold text-rose-600 disabled:opacity-30">Hapus</button></div>
                        <div class="mb-3 flex items-end gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-700 text-white">
                                @foreach(\App\Support\TestPreparationIcons::OPTIONS as $iconKey => $iconLabel)
                                    <x-test-preparation-icon :icon="$iconKey" x-show="item.icon === '{{ $iconKey }}'" />
                                @endforeach
                            </span>
                            <label class="block min-w-0 flex-1 text-sm font-bold text-slate-700">Ikon
                                <select :name="'items[' + index + '][icon]'" x-model="item.icon" required class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                                    @foreach(\App\Support\TestPreparationIcons::OPTIONS as $iconKey => $iconLabel)
                                        <option value="{{ $iconKey }}">{{ $iconLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <label class="block text-sm font-bold text-slate-700">Judul
                            <input :name="'items[' + index + '][title]'" x-model="item.title" required maxlength="150" placeholder="Contoh: Bawa HP dan internet" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                        </label>
                        <label class="mt-3 block text-sm font-bold text-slate-700">Penjelasan
                            <textarea :name="'items[' + index + '][body]'" x-model="item.body" required maxlength="600" rows="3" placeholder="Jelaskan apa yang perlu disiapkan siswa." class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5"></textarea>
                        </label>
                    </div>
                </template>
            </div>
            <div class="mt-4 flex flex-wrap gap-3"><button type="button" :disabled="items.length >= 12" @click="items.push({title:'', body:'', icon:'document'})" class="rounded-xl border border-teal-200 px-4 py-2.5 text-sm font-bold text-teal-700">+ Tambah persiapan</button><button class="rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-black text-white">Simpan persiapan</button></div>
        </form>
    </details>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-black text-slate-950">Daftar Jadwal Tes</h2>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600">{{ $jadwalTes->count() }} data</span>
                </div>
                <p class="mt-0.5 text-xs text-slate-500">Edit tanggal/jam untuk mengundur tes. Siswa yang memilih jadwal tersebut mendapat WhatsApp otomatis. Untuk tanggal baru, tambahkan jadwal lalu pindahkan siswa.</p>
            </div>
            <button type="button" @click="addOpen=true" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-sm hover:bg-emerald-700">+ Tambah Jadwal Tes</button>
        </div>

        <div class="grid gap-3 p-4">
            @forelse($jadwalTes as $jadwal)
                <article class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 transition hover:border-emerald-200 hover:bg-white">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-black text-slate-950">{{ $jadwal->kegiatan }}</h3>
                                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-600 ring-1 ring-slate-200">{{ $jadwal->tahunAjaran?->name ?? '-' }}</span>
                            </div>
                            @if($jadwal->keterangan)
                                <p class="mt-1 max-w-3xl text-sm font-medium text-slate-500">{{ $jadwal->keterangan }}</p>
                            @endif
                        </div>

                        <div class="grid gap-2 text-sm sm:grid-cols-2 lg:w-[520px]">
                            <div class="rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-200">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Jadwal</p>
                                <p class="mt-1 font-black text-slate-800">{{ \Carbon\Carbon::parse($jadwal->tanggal_mulai)->translatedFormat('d M Y H:i') }}</p>
                                @if($jadwal->available_for_student_selection)<span class="mt-2 inline-flex rounded-full bg-teal-100 px-2 py-1 text-[10px] font-black text-teal-700">Pilihan siswa dibuka</span>@endif
                                @if($jadwal->tanggal_selesai)
                                    <p class="text-xs font-bold text-slate-500">sampai {{ \Carbon\Carbon::parse($jadwal->tanggal_selesai)->translatedFormat('d M Y H:i') }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 gap-2 lg:justify-end">
                            <button type="button" @click="editId={{ $jadwal->id }}" class="rounded-lg bg-sky-100 px-4 py-2.5 text-xs font-black text-sky-700">Edit</button>
                            <form method="POST" action="{{ route('admin.informasi-tes.destroy', $jadwal) }}" onsubmit="return confirm('Hapus jadwal tes ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg bg-rose-100 px-4 py-2.5 text-xs font-black text-rose-700">Hapus</button>
                            </form>
                        </div>
                    </div>
                    @php
                        $replacements = $jadwalTes->filter(fn ($item) => $item->id !== $jadwal->id && $item->tahun_ajaran_id == $jadwal->tahun_ajaran_id && $item->available_for_student_selection && \Carbon\Carbon::parse($item->tanggal_mulai)->gte(now()->startOfDay()));
                    @endphp
                    @if($replacements->isNotEmpty())
                        <details class="mt-4 border-t border-slate-200 pt-3">
                            <summary class="cursor-pointer text-sm font-bold text-teal-700">Pindahkan siswa ke jadwal lain</summary>
                            <form method="POST" action="{{ route('admin.informasi-tes.move', $jadwal) }}" class="mt-3 flex flex-col gap-3 sm:flex-row" onsubmit="return confirm('Pindahkan semua siswa yang memilih jadwal ini dan kirim pemberitahuan WhatsApp? Jadwal lama akan ditutup untuk pilihan baru.')">
                                @csrf
                                <select name="replacement_schedule_id" required aria-label="Jadwal pengganti" class="min-w-0 flex-1 rounded-xl border border-teal-200 px-3 py-2 text-sm">
                                    <option value="">Pilih jadwal pengganti</option>
                                    @foreach($replacements as $replacement)
                                        <option value="{{ $replacement->id }}">{{ \App\Services\TestScheduleService::date($replacement->tanggal_mulai) }} — {{ $replacement->kegiatan }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-bold text-white">Pindahkan &amp; beri tahu siswa</button>
                            </form>
                        </details>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-10 text-center">
                    <p class="font-black text-slate-700">Belum ada jadwal tes SPMB.</p>
                    <p class="mt-1 text-sm text-slate-500">Klik tambah jadwal untuk mulai mengisi data tes.</p>
                </div>
            @endforelse
        </div>
    </section>

    <template x-teleport="body">
    <div x-cloak x-show="addOpen" x-transition.opacity class="test-schedule-overlay" @keydown.escape.window="addOpen=false">
        <div role="dialog" aria-modal="true" aria-labelledby="add-test-title" @click.outside="addOpen=false" class="test-schedule-dialog">
            <div class="flex items-start justify-between border-b border-slate-100 p-5">
                <div>
                    <h3 id="add-test-title" class="text-lg font-black text-slate-950">Tambah Jadwal Tes</h3>
                    <p class="mt-1 text-xs text-slate-500">Isi jadwal sesuai keputusan sekolah.</p>
                </div>
                <button type="button" @click="addOpen=false" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.informasi-tes.store') }}" class="space-y-4 p-5">
                @csrf
                <div class="test-schedule-fields">
                @include('admin.informasi-tes.partials.form', ['jadwal' => null])
                @if($errors->any())<p class="rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-600">{{ $errors->first() }}</p>@endif
                </div>
                <div class="test-schedule-footer grid grid-cols-2 gap-2">
                    <button type="button" @click="addOpen=false" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600">Batal</button>
                    <button class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    </template>

    @foreach($jadwalTes as $jadwal)
        <template x-teleport="body">
        <div x-cloak x-show="editId==={{ $jadwal->id }}" x-transition.opacity class="test-schedule-overlay" @keydown.escape.window="editId=null">
            <div role="dialog" aria-modal="true" aria-label="Edit Jadwal Tes" @click.outside="editId=null" class="test-schedule-dialog">
                <div class="flex items-start justify-between border-b border-slate-100 p-5">
                    <div>
                        <h3 class="text-lg font-black text-slate-950">Edit Jadwal Tes</h3>
                        <p class="mt-1 text-xs text-slate-500">Ubah jadwal, jam, atau keterangan.</p>
                    </div>
                    <button type="button" @click="editId=null" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.informasi-tes.update', $jadwal) }}" class="space-y-4 p-5">
                    @csrf
                    @method('PUT')
                    <div class="test-schedule-fields">
                    @include('admin.informasi-tes.partials.form', ['jadwal' => $jadwal])
                    </div>
                    <div class="test-schedule-footer grid grid-cols-2 gap-2">
                        <button type="button" @click="editId=null" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600">Batal</button>
                        <button class="rounded-xl bg-sky-600 px-4 py-3 text-sm font-black text-white">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
        </template>
    @endforeach
</div>
@endsection
