@extends('layouts.admin')

@section('title', 'Informasi Tes')
@section('page_title', 'Kelola Informasi Tes')

@section('content')
<div x-data="{ addOpen: {{ $errors->any() ? 'true' : 'false' }}, editId: null }">
    @php $preparationText = collect($testPreparation)->map(fn ($item) => ($item['title'] ?? '').'|'.($item['body'] ?? ''))->implode("\n"); @endphp
    <section class="mb-5 rounded-2xl border border-teal-200 bg-teal-50/60 p-5 shadow-sm">
        <p class="text-xs font-black uppercase tracking-[.14em] text-teal-700">Tampilan siswa</p>
        <h2 class="mt-1 text-lg font-black text-slate-950">Persiapan Tes SPMB</h2>
        <p class="mt-1 text-sm font-medium text-slate-600">Isi ini tampil pada halaman Tes SPMB siswa sebelum panitia membuka CBT.</p>
        <form method="POST" action="{{ route('admin.informasi-tes.preparation.save') }}" class="mt-4">@csrf
            <label class="text-xs font-black text-slate-700">Persiapan <span class="font-medium text-slate-500">satu baris: Judul|Penjelasan</span></label>
            <textarea name="items_text" rows="5" required class="mt-2 w-full rounded-2xl border-2 border-teal-100 bg-white px-4 py-3 text-sm font-semibold text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-100">{{ old('items_text', $preparationText) }}</textarea>
            <button class="mt-3 rounded-xl bg-teal-700 px-4 py-2.5 text-xs font-black text-white shadow-sm hover:bg-teal-800">Simpan persiapan tes</button>
        </form>
    </section>
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

    <div x-cloak x-show="addOpen" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm" @keydown.escape.window="addOpen=false">
        <div @click.outside="addOpen=false" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-100 p-5">
                <div>
                    <h3 class="text-lg font-black text-slate-950">Tambah Jadwal Tes</h3>
                    <p class="mt-1 text-xs text-slate-500">Isi jadwal sesuai keputusan sekolah.</p>
                </div>
                <button type="button" @click="addOpen=false" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.informasi-tes.store') }}" class="space-y-4 p-5">
                @csrf
                @include('admin.informasi-tes.partials.form', ['jadwal' => null])
                @if($errors->any())<p class="rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-600">{{ $errors->first() }}</p>@endif
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="addOpen=false" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600">Batal</button>
                    <button class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($jadwalTes as $jadwal)
        <div x-cloak x-show="editId==={{ $jadwal->id }}" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm" @keydown.escape.window="editId=null">
            <div @click.outside="editId=null" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
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
                    @include('admin.informasi-tes.partials.form', ['jadwal' => $jadwal])
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
