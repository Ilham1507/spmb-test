@extends($layout)

@section('title', 'Detail Hasil Tes')
@section('page_title', 'Detail Hasil Tes')
@section('page_description', 'Nilai dan data pelaksanaan tes peserta.')

@section('content')
<div class="test-result-detail mx-auto max-w-[1080px] space-y-4">
    <section class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div><p class="interview-session-kicker">Detail peserta</p><h2 class="text-lg font-black text-slate-950">{{ $pendaftar->biodata?->full_name ?? 'Belum isi nama' }}</h2><p class="mt-1 text-xs font-semibold text-slate-500">{{ $pendaftar->registration_number ?? '-' }} · {{ $pendaftar->jurusan1?->name ?? 'Jurusan belum dipilih' }}</p></div>
        <a href="{{ route($backRoute) }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-slate-100 px-4 text-xs font-black text-slate-700">Kembali ke daftar</a>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-3"><h3 class="text-base font-black text-slate-950">Hasil setiap tes</h3><p class="mt-1 text-xs font-semibold text-slate-500">Nilai dan catatan dicatat oleh petugas sesuai tahap tes.</p></div>
        <div class="divide-y divide-slate-100">
            @forelse($primaryResults as $result)
                @php $testName = $result->tes?->test_name ?? 'Tes'; @endphp
                <article class="grid gap-2 px-5 py-4 sm:grid-cols-[minmax(180px,.8fr)_minmax(140px,.55fr)_minmax(0,1.4fr)] sm:items-center">
                    <div><h4 class="text-sm font-black text-slate-900">{{ $testName }}</h4><p class="mt-1 text-[11px] font-semibold text-slate-500">{{ $result->updated_at?->timezone('Asia/Jakarta')->format('d M Y, H:i') }}</p></div>
                    <div><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Hasil</p><strong class="mt-1 block text-sm text-sky-700">{{ filled($result->score) ? 'Nilai ' . $result->score : ($testName === 'Tes Kesehatan' ? 'Data pemeriksaan tersedia' : ($testName === 'Tes Ukuran Seragam' ? 'Ukuran tersedia' : 'Tercatat')) }}</strong></div>
                    @if($canViewNotes)<div><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Catatan</p><p class="mt-1 text-xs font-semibold leading-relaxed text-slate-600">{{ $result->notes ?: '-' }}</p></div>@endif
                </article>
            @empty
                <p class="p-8 text-center text-sm font-semibold text-slate-400">Belum ada hasil tes tercatat.</p>
            @endforelse
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-3"><h3 class="text-base font-black text-slate-950">Ukuran seragam</h3></div>
            @if($uniformResult)
                <div class="p-5"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Ukuran yang dicatat</p><strong class="mt-1 block text-2xl font-black text-sky-700">{{ $uniformSize?->name ?? 'Ukuran khusus' }}</strong>@if($canViewNotes && $uniformResult->notes)<p class="mt-3 text-xs font-semibold leading-relaxed text-slate-600">{{ $uniformResult->notes }}</p>@endif</div>
            @else
                <p class="p-5 text-sm font-semibold text-slate-400">Data ukuran seragam belum dicatat.</p>
            @endif
        </section>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-3"><h3 class="text-base font-black text-slate-950">Pemeriksaan kesehatan</h3></div>
            <div class="divide-y divide-slate-100">
                @forelse($healthResults as $health)
                    @php $item = $healthItems->get($health->health_check_item_id); @endphp
                    <div class="grid grid-cols-[1fr_auto] gap-3 px-5 py-3"><div><p class="text-sm font-black text-slate-900">{{ $item?->name ?? 'Pemeriksaan' }}</p>@if($canViewNotes && $health->notes)<p class="mt-1 text-[11px] font-semibold text-slate-500">{{ $health->notes }}</p>@endif</div><div class="text-right"><strong class="block text-sm text-sky-700">{{ $health->result_value ?: '-' }}{{ $item?->unit ? ' ' . $item->unit : '' }}</strong><span class="mt-1 inline-block rounded-full bg-slate-100 px-2 py-1 text-[10px] font-black text-slate-600">{{ $health->result_status ? \Illuminate\Support\Str::of($health->result_status)->replace('_', ' ')->title() : 'Dicatat' }}</span></div></div>
                @empty
                    <p class="p-5 text-sm font-semibold text-slate-400">Data pemeriksaan kesehatan belum dicatat.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
