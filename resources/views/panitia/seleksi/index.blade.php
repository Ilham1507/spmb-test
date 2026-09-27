@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.panitia')

@section('title', 'Seleksi Akhir')
@section('page_title', 'Seleksi Akhir Pendaftar')

@section('content')
<div class="selection-workspace space-y-5">
    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Stats --}}
    @php
        $allItems = $pendaftars->getCollection();
        $verifiedCount = $allItems->where('registration_status', 'verified')->count();
        $acceptedCount = $allItems->where('registration_status', 'accepted')->count();
        $rejectedCount = $allItems->where('registration_status', 'rejected')->count();
    @endphp
    <div class="selection-summary grid grid-cols-3 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-r border-slate-200 px-4 py-3">
            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Siap Diseleksi</p>
            <p class="mt-0.5 text-xl font-black text-emerald-700">{{ $verifiedCount }}</p>
        </div>
        <div class="border-r border-slate-200 px-4 py-3">
            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Diterima</p>
            <p class="mt-0.5 text-xl font-black text-emerald-700">{{ $acceptedCount }}</p>
        </div>
        <div class="px-4 py-3">
            <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Ditolak</p>
            <p class="mt-0.5 text-xl font-black text-rose-700">{{ $rejectedCount }}</p>
        </div>
    </div>

    <div class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm lg:flex-row lg:items-center">
        <form method="GET" class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
            <x-list-search placeholder="Cari nama atau nomor pendaftaran" class="flex-1" />
            <button class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white">Cari</button>
            @if(request('search'))<a href="{{ route(request()->routeIs('admin.*') ? 'admin.seleksi.index' : 'panitia.seleksi.index') }}" class="rounded-xl bg-slate-100 px-4 py-2.5 text-center text-xs font-black text-slate-600">Reset</a>@endif
        </form>
        <x-per-page-pagination :paginator="$pendaftars" static />
    </div>

    {{-- Table --}}
    <div class="selection-table relative rounded-2xl border border-slate-200 bg-white shadow-sm md:overflow-visible">
        <div class="overflow-x-auto md:overflow-visible">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                        <th class="p-4 pl-6">Nama Pendaftar</th>
                        <th class="p-4">Pilihan Jurusan</th>
                        <th class="p-4">Keputusan</th>
                        <th class="p-4 pr-6 text-right">Keputusan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($pendaftars as $p)
                    <tr class="hover:bg-slate-50/50 transition-colors" id="row-{{ $p->id }}">
                        <td class="p-4 pl-6">
                            <p class="font-semibold text-slate-900">{{ $p->biodata?->full_name ?? '-' }}</p>
                            <p class="text-xs text-slate-400 font-mono">{{ $p->registration_number ?? '' }}</p>
                        </td>
                        <td class="p-4">
                            <div class="space-y-1 text-xs font-semibold text-slate-600">
                                <p><span class="mr-1 text-slate-400">1.</span>{{ $p->jurusan1?->name ?? '-' }}</p>
                                <p><span class="mr-1 text-slate-400">2.</span>{{ $p->jurusan2?->name ?? '-' }}</p>
                            </div>
                        </td>
                        <td class="p-4">
                            @php
                                $statusColors = [
                                    'verified' => 'bg-emerald-100 text-emerald-700',
                                    'accepted' => 'bg-emerald-100 text-emerald-700',
                                    'rejected' => 'bg-rose-100 text-rose-700',
                                ];
                                $statusLabels = [
                                    'verified' => 'Siap diputuskan',
                                    'accepted' => 'Diterima',
                                    'rejected' => 'Ditolak',
                                ];
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $statusColors[$p->registration_status] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $statusLabels[$p->registration_status] ?? ucfirst($p->registration_status) }}
                            </span>
                            @if($p->hasilSeleksi)
                                <div class="mt-1.5 text-xs leading-snug text-slate-500">
                                    @if($p->registration_status === 'accepted')
                                        <p class="font-semibold text-slate-600">{{ $p->hasilSeleksi->major?->name ?? 'Jurusan belum tercatat' }}</p>
                                        <p class="mt-1">oleh {{ $p->hasilSeleksi->decisionMaker?->name ?? 'Panitia' }}</p>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="p-4 pr-6 text-right">
                            @if($p->registration_status === 'verified')
                            <div class="flex flex-nowrap items-center justify-end gap-2">
                                @php
                                    $majorOptions = collect([$p->jurusan1, $p->jurusan2])
                                        ->filter()->unique('id')->mapWithKeys(fn ($major) => [$major->id => $major->name]);
                                @endphp
                                <form method="POST" action="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . 'seleksi.decide', $p->id) }}" data-student="{{ $p->biodata?->full_name ?? 'Pendaftar' }}" class="selection-form selection-accept-form flex flex-nowrap items-center justify-end gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="accepted">
                                    <div class="selection-major-field w-56 text-left">
                                        <x-form-select name="major_id" :options="$majorOptions" placeholder="Pilih jurusan diterima" required :id="'selection-major-'.$p->id" />
                                    </div>
                                    <button type="submit" disabled
                                            class="selection-accept-button inline-flex h-10 cursor-not-allowed items-center gap-1 rounded-xl bg-slate-300 px-3 text-[11px] font-bold text-white opacity-70 transition-colors disabled:pointer-events-none">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                        Terima
                                    </button>
                                </form>
                                <form method="POST" action="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . 'seleksi.decide', $p->id) }}" data-student="{{ $p->biodata?->full_name ?? 'Pendaftar' }}" class="selection-form inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit"
                                            class="inline-flex h-10 items-center gap-1 whitespace-nowrap rounded-xl bg-rose-600 px-3 text-[11px] font-bold text-white transition-colors hover:bg-rose-700">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                                        Tolak
                                    </button>
                                </form>
                            </div>
                            @else
                                <span class="text-xs text-slate-400 italic">Sudah diputuskan</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-slate-500 font-medium">Belum ada pendaftar yang siap diseleksi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .selection-major-field button[id^="selection-major-"] { min-height: 2.5rem; padding: .4rem .7rem; border-radius: .75rem; }
    .selection-major-field button[id^="selection-major-"] > span > span:first-child { width: 1.75rem; height: 1.75rem; border-radius: .55rem; }
</style>

<div id="selection-confirm-modal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="selection-modal-title">
    <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div id="selection-modal-accent" class="h-2 bg-emerald-500"></div>
        <div class="p-6">
            <div id="selection-modal-icon" class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 id="selection-modal-title" class="text-xl font-black text-slate-950">Konfirmasi keputusan</h3>
            <p id="selection-modal-message" class="mt-2 text-sm font-semibold leading-relaxed text-slate-600"></p>
            <div id="selection-modal-major-wrap" class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-wide text-emerald-600">Jurusan yang disetujui</p>
                <p id="selection-modal-major" class="mt-1 font-black text-emerald-900"></p>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button id="selection-modal-cancel" type="button" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-200">Periksa Lagi</button>
                <button id="selection-modal-confirm" type="button" class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">Ya, Terima</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const selectionModal = document.getElementById('selection-confirm-modal');
    const selectionMessage = document.getElementById('selection-modal-message');
    const selectionMajorWrap = document.getElementById('selection-modal-major-wrap');
    const selectionMajorText = document.getElementById('selection-modal-major');
    const selectionConfirm = document.getElementById('selection-modal-confirm');
    const selectionAccent = document.getElementById('selection-modal-accent');
    const selectionIcon = document.getElementById('selection-modal-icon');
    let pendingSelectionForm = null;

    document.querySelectorAll('.selection-accept-form').forEach((form) => {
        const major = form.querySelector('input[name="major_id"]');
        const button = form.querySelector('.selection-accept-button');
        if (!major || !button) return;

        const syncAcceptButton = (selectedValue = major.value) => {
            const ready = selectedValue !== '';
            button.disabled = !ready;
            button.classList.toggle('cursor-not-allowed', !ready);
            button.classList.toggle('opacity-70', !ready);
            button.classList.toggle('bg-slate-300', !ready);
            button.classList.toggle('bg-emerald-600', ready);
            button.classList.toggle('hover:bg-emerald-700', ready);
        };

        major.addEventListener('change', () => syncAcceptButton());
        document.addEventListener('form-select-changed', (event) => {
            if (event.detail?.name === 'major_id') {
                window.setTimeout(() => syncAcceptButton(), 0);
            }
        });
        syncAcceptButton();
    });

    document.querySelectorAll('.selection-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') return;
            event.preventDefault();

            const accepted = form.querySelector('input[name="status"]')?.value === 'accepted';
            const student = form.dataset.student;
            const majorInput = form.querySelector('input[name="major_id"]');
            const majorButton = majorInput?.parentElement?.querySelector('button span[x-text]');

            pendingSelectionForm = form;
            selectionMessage.textContent = accepted
                ? `Pastikan ${student} benar-benar diterima pada jurusan berikut.`
                : `Yakin ingin menolak ${student}? Keputusan ini akan tercatat atas nama akun Anda.`;
            selectionMajorWrap.classList.toggle('hidden', !accepted);
            selectionMajorText.textContent = accepted ? (majorButton?.textContent?.trim() || 'Jurusan terpilih') : '';
            selectionConfirm.textContent = accepted ? 'Ya, Terima Siswa' : 'Ya, Tolak Siswa';
            selectionConfirm.className = accepted
                ? 'rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700'
                : 'rounded-2xl bg-rose-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-rose-200 hover:bg-rose-700';
            selectionAccent.className = accepted ? 'h-2 bg-emerald-500' : 'h-2 bg-rose-500';
            selectionIcon.className = accepted
                ? 'mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700'
                : 'mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 text-rose-700';
            selectionModal.classList.remove('hidden');
            selectionModal.classList.add('flex');
        });
    });

    const closeSelectionModal = () => {
        selectionModal.classList.add('hidden');
        selectionModal.classList.remove('flex');
        pendingSelectionForm = null;
    };

    document.getElementById('selection-modal-cancel')?.addEventListener('click', closeSelectionModal);
    selectionModal?.addEventListener('click', (event) => { if (event.target === selectionModal) closeSelectionModal(); });
    selectionConfirm?.addEventListener('click', () => {
        if (!pendingSelectionForm) return;
        pendingSelectionForm.dataset.confirmed = 'true';
        pendingSelectionForm.requestSubmit();
    });
</script>
@endpush
@endsection
