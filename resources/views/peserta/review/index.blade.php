@extends('layouts.peserta')

@section('title', 'Review Pendaftaran')
@section('page_title', 'Review Pendaftaran')

@section('content')
@php
    use App\Support\FormFieldCatalog;
    $isSubmitted = $pendaftar->registration_status === 'submitted';
    $groups = FormFieldCatalog::groups();
    $sections = [
        ['group' => 'Biodata', 'title' => 'Biodata diri', 'route' => 'peserta.biodata', 'key' => 'nama_peserta'],
        ['group' => 'Alamat', 'title' => 'Alamat domisili', 'route' => 'peserta.alamat', 'key' => 'alamat'],
        ['group' => 'Ayah', 'title' => 'Data ayah', 'route' => 'peserta.ayah', 'key' => 'nama_ayah'],
        ['group' => 'Ibu', 'title' => 'Data ibu', 'route' => 'peserta.ibu', 'key' => 'nama_ibu'],
        ['group' => 'Wali', 'title' => 'Data wali', 'route' => 'peserta.wali', 'key' => 'nama_wali'],
        ['group' => 'Sekolah Asal', 'title' => 'Sekolah asal', 'route' => 'peserta.sekolah', 'key' => 'asal_sekolah'],
        ['group' => 'Pilihan Jurusan', 'title' => 'Pilihan jurusan', 'route' => 'peserta.jurusan', 'key' => 'jurusan'],
        ['group' => 'Kontak', 'title' => 'Data kontak', 'route' => 'peserta.kontak', 'key' => 'no_handphone'],
    ];
    $formatValue = function ($key, $value) use ($pendaftar) {
        if (!FormFieldCatalog::isCompleteValue($value)) return 'Belum diisi';
        if ($key === 'jenis_kelamin') return $value === 'L' ? 'Laki-laki' : 'Perempuan';
        if ($key === 'tanggal_lahir') return \Illuminate\Support\Carbon::parse($value)->format('d M Y');
        if ($key === 'jurusan') return $pendaftar->jurusan1?->name ?? 'Belum diisi';
        return $value;
    };
    $activeSections = collect($sections)->map(function ($section) use ($groups) {
        $section['fields'] = collect($groups[$section['group']] ?? [])->filter(fn ($label, $key) => FormFieldCatalog::isEnabled($key));
        return $section;
    })->filter(fn ($section) => $section['fields']->isNotEmpty())->values();
    $completeSections = $activeSections->filter(function ($section) use ($pendaftar) {
        return $section['fields']->filter(fn ($label, $key) => FormFieldCatalog::isRequired($key))
            ->every(fn ($label, $key) => FormFieldCatalog::isCompleteValue(FormFieldCatalog::valueFor($pendaftar, $key)));
    })->count();
@endphp

<div class="participant-review mx-auto max-w-4xl space-y-4">
    <section class="participant-review-hero"><div class="participant-review-orbit participant-review-orbit-one"></div><div class="participant-review-orbit participant-review-orbit-two"></div><div class="relative flex items-start gap-4"><span class="participant-review-check"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg></span><div class="min-w-0 flex-1"><p class="participant-review-kicker">LANGKAH TERAKHIR</p><h2>{{ $isSubmitted ? 'Pendaftaran sudah dikirim' : 'Periksa data pendaftaran' }}</h2><p>{{ $isSubmitted ? 'Data Anda sedang diproses oleh panitia.' : 'Buka bagian yang ingin dicek atau diperbaiki.' }}</p></div><div class="participant-review-progress"><strong>{{ $completeSections }}/{{ $activeSections->count() }}</strong><span>lengkap</span></div></div><div class="relative mt-5 flex flex-wrap gap-2 text-xs font-bold text-teal-50/90"><span class="rounded-full bg-white/15 px-3 py-1.5">No. {{ $pendaftar->registration_number }}</span></div></section>

    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>@endif

    <div class="participant-review-list">
        @foreach($activeSections as $index => $section)
            @php
                $primaryValue = FormFieldCatalog::valueFor($pendaftar, $section['key']);
                $requiredComplete = $section['fields']->filter(fn ($label, $key) => FormFieldCatalog::isRequired($key))->every(fn ($label, $key) => FormFieldCatalog::isCompleteValue(FormFieldCatalog::valueFor($pendaftar, $key)));
            @endphp
            <details class="participant-review-card" @if($index === 0) open @endif>
                <summary><span class="participant-review-icon {{ $requiredComplete ? 'is-ready' : '' }}">{{ $requiredComplete ? '✓' : $index + 1 }}</span><span class="min-w-0 flex-1"><b>{{ $section['title'] }}</b><small>{{ $formatValue($section['key'], $primaryValue) }}</small></span>@unless($isSubmitted)<a href="{{ route($section['route'], ['return_to' => 'review']) }}" onclick="event.stopPropagation()">Ubah</a>@endunless<span class="participant-review-chevron">⌄</span></summary>
                <div class="participant-review-body grid sm:grid-cols-2">
                    @foreach($section['fields'] as $key => $label)
                        @if($key === 'jurusan')
                            <p><span>Jurusan pilihan 1</span>{{ $pendaftar->jurusan1?->name ?? 'Belum diisi' }}</p><p><span>Jurusan pilihan 2</span>{{ $pendaftar->jurusan2?->name ?? '-' }}</p><p><span>Jalur pendaftaran</span>{{ $pendaftar->jalurPendaftaran?->name ?? 'Belum diisi' }}</p>
                        @else
                            @php $rawValue = $key === 'email' ? $pendaftar->kontak?->email : FormFieldCatalog::valueFor($pendaftar, $key); @endphp
                            <p><span>{{ $label }} @if(FormFieldCatalog::isRequired($key))<em class="text-rose-500">*</em>@endif</span>{{ $formatValue($key, $rawValue) }}@if($key === 'email' && $rawValue && ! $pendaftar->kontak?->email_verified_at)<b class="mt-1 block text-amber-600">Belum diverifikasi</b>@endif</p>
                        @endif
                    @endforeach
                </div>
            </details>
        @endforeach

        <details class="participant-review-card">
            <summary><span class="participant-review-icon {{ $jenisDokumens->where('is_required', true)->every(fn ($doc) => $uploadedDocs->has($doc->id)) ? 'is-ready' : '' }}">✓</span><span class="min-w-0 flex-1"><b>Dokumen persyaratan</b><small>{{ $uploadedDocs->count() }} dokumen diunggah</small></span>@unless($isSubmitted)<a href="{{ route('peserta.dokumen', ['return_to' => 'review']) }}" onclick="event.stopPropagation()">Ubah</a>@endunless<span class="participant-review-chevron">⌄</span></summary>
            <div class="participant-review-body participant-review-docs">@foreach($jenisDokumens as $doc)<p><span>{{ $doc->name }} @if($doc->is_required)<em class="text-rose-500">*</em>@endif</span><b class="{{ $uploadedDocs->has($doc->id) ? 'text-emerald-700' : 'text-slate-400' }}">{{ $uploadedDocs->has($doc->id) ? 'Sudah diunggah' : 'Belum diunggah' }}</b></p>@endforeach</div>
        </details>
    </div>

    @if($isSubmitted)<div class="participant-review-finished"><span>✓</span><div><b>Pendaftaran terkirim</b><p>Panitia akan memeriksa data Anda.</p></div></div>
    @else
        <form method="POST" action="{{ route('peserta.submit') }}" x-data="{ confirmSubmit: false, scheduleRequired: false, submitting: false }" @submit.prevent="if (!$el.querySelector('input[name=preferred_test_schedule_id]:checked')) { scheduleRequired = true; $nextTick(() => document.getElementById('pilihan-tanggal-tes')?.scrollIntoView({ behavior: 'smooth', block: 'center' })); } else { confirmSubmit = true }">
            @csrf
            <section id="pilihan-tanggal-tes" class="rounded-3xl border border-teal-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.14em] text-teal-700">Kehadiran tes SPMB</p>
                <h3 class="mt-2 text-xl font-black text-slate-900">Pilih tanggal tes</h3>
                <p class="mt-1 text-sm font-medium text-slate-500">Pilih satu tanggal yang bisa kamu hadiri.</p>
                @if($testSchedules->isNotEmpty())
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach($testSchedules as $schedule)
                            @php $startsAt = \Illuminate\Support\Carbon::parse($schedule->tanggal_mulai); @endphp
                            <label class="relative flex cursor-pointer items-center gap-4 rounded-2xl border-2 border-slate-100 bg-white p-4 shadow-sm transition hover:border-teal-300 has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50 has-[:checked]:shadow-teal-100">
                                <input class="h-5 w-5 shrink-0 accent-teal-700" type="radio" name="preferred_test_schedule_id" value="{{ $schedule->id }}" required @checked((int) old('preferred_test_schedule_id', $pendaftar->preferred_test_schedule_id) === $schedule->id)>
                                <span class="min-w-0 flex-1">
                                    <b class="block text-lg font-black text-slate-900">{{ $startsAt->translatedFormat('d F Y') }}</b>
                                    <span class="mt-1 block text-sm font-bold text-teal-700">{{ $startsAt->format('H:i') }} WIB</span>
                                    <span class="mt-2 block text-xs font-semibold text-slate-500">Kampus E SMK Muhammadiyah 4 Cileungsi</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">Tanggal tes belum dibuka oleh sekolah. Hubungi panitia untuk informasi jadwal berikutnya.</div>
                @endif
            </section>
            <button type="submit" @disabled($testSchedules->isEmpty()) class="participant-review-submit mt-4 disabled:cursor-not-allowed disabled:opacity-50"><span>✓</span>Kirim pendaftaran <i>→</i></button>
            <div x-cloak x-show="scheduleRequired" x-transition.opacity class="fixed inset-0 z-[100] flex items-end bg-slate-950/60 p-4 backdrop-blur-sm sm:items-center sm:justify-center" @keydown.escape.window="scheduleRequired = false">
                <div x-show="scheduleRequired" x-transition.scale.origin.bottom class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl">
                    <span class="participant-review-modal-icon !bg-amber-100 !text-amber-700">!</span>
                    <h3>Pilih tanggal tes dulu</h3>
                    <p>Pilih satu tanggal Tes SPMB yang bisa kamu hadiri sebelum mengirim pendaftaran.</p>
                    <button type="button" class="participant-review-modal-submit mt-6 w-full py-3" @click="scheduleRequired = false">Mengerti</button>
                </div>
            </div>
            <div x-cloak x-show="confirmSubmit" x-transition.opacity class="fixed inset-0 z-[90] flex items-end bg-slate-950/60 p-4 backdrop-blur-sm sm:items-center sm:justify-center"><div x-show="confirmSubmit" x-transition.scale.origin.bottom class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl"><span class="participant-review-modal-icon">✓</span><h3>Siap kirim pendaftaran?</h3><p>Pastikan data, dokumen, dan pilihan tanggal tes sudah benar. Setelah dikirim, formulir akan diperiksa panitia.</p><div class="mt-6 flex gap-3"><button type="button" class="btn-secondary flex-1" :disabled="submitting" @click="confirmSubmit = false">Cek lagi</button><button type="button" class="participant-review-modal-submit flex-1 disabled:opacity-60" :disabled="submitting" @click="if (!submitting) { submitting = true; $el.closest('form').submit() }" x-text="submitting ? 'Mengirim…' : 'Kirim'">Kirim</button></div></div></div>
        </form>
    @endif
</div>
@endsection



