@extends(auth()->user()?->hasRole('kepala_sekolah') ? 'layouts.kepala-sekolah' : (request()->routeIs('admin.*') ? 'layouts.admin' : (request()->routeIs('bendahara.*') ? 'layouts.bendahara' : 'layouts.panitia')))

@section('title', 'Tes CBT')
@section('page_title', 'Tes CBT')

@section('content')
@php
    $routePrefix = request()->routeIs('admin.*') ? 'admin.' : (request()->routeIs('bendahara.*') ? 'bendahara.' : 'panitia.');
    $durationOptions = ['30' => '30 menit', '45' => '45 menit', '60' => '60 menit', '90' => '90 menit', '120' => '120 menit'];
    $answerOptions = ['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D'];
@endphp

<div class="test-cbt-workspace mx-auto max-w-[1180px] space-y-3">
    <section class="test-cbt-queue rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-4 py-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-black text-slate-950">Peserta</h2>
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-black text-emerald-700">{{ $activeApplicantIds->count() }} aktif</span>
                </div>
            </div>

            <form method="GET" action="{{ route($routePrefix . 'tes.cbt') }}" class="grid gap-2 sm:min-w-[360px] sm:grid-cols-[1fr_auto]">
                <x-list-search placeholder="Cari siswa" class="min-w-0" />
                <button class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white">Cari</button>
            </form>
        </div>

        <form method="POST" action="{{ $canManageTests ? route($routePrefix . 'tes.cbt.open-bulk') : '#' }}" class="p-4">
            @csrf
            <div class="flex items-center justify-between px-1 pb-2">
                <label class="flex cursor-pointer items-center gap-2 text-xs font-black text-slate-700">
                    <input id="select-all-cbt" type="checkbox" @disabled(! $canManageTests) class="h-4 w-4 rounded text-emerald-600">
                    Pilih semua
                </label>
                <span class="text-xs font-bold text-slate-500"><span id="selected-cbt-count">0</span> dipilih</span>
            </div>

            <div class="max-h-[170px] overflow-y-auto rounded-xl border border-slate-200">
                <div class="divide-y divide-slate-100">
                @forelse($pendingApplicants as $applicant)
                    @php $isActive = $activeApplicantIds->contains($applicant->id); @endphp
                    <label class="flex cursor-pointer items-center gap-3 px-3 py-2.5 {{ $isActive ? 'bg-emerald-50' : 'hover:bg-slate-50' }}">
                        <input type="checkbox" name="applicant_ids[]" value="{{ $applicant->id }}" @disabled(! $canManageTests) class="cbt-applicant h-4 w-4 rounded text-emerald-600">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-black text-slate-900">{{ $applicant->biodata?->full_name ?? 'Belum isi nama' }}</span>
                            <span class="block truncate text-[11px] font-semibold text-slate-500">{{ $applicant->registration_number }} - {{ $applicant->jurusan1?->name ?? '-' }}</span>
                        </span>
                        @if($isActive)
                            <span class="rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-black text-emerald-700">AKTIF</span>
                        @endif
                    </label>
                @empty
                    <p class="p-5 text-center text-sm text-slate-400">Tidak ada peserta yang menunggu CBT.</p>
                @endforelse
                </div>
            </div>

            <div class="test-cbt-launch mt-3 flex flex-wrap items-center gap-2">
                <div class="cbt-duration-picker" data-cbt-duration>
                    <span>Durasi</span>
                    <input type="hidden" name="duration_minutes" value="60">
                    <button type="button" class="cbt-duration-trigger" aria-expanded="false"><b>60 menit</b><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></button>
                    <div class="cbt-duration-menu" hidden>
                        @foreach($durationOptions as $value => $label)<button type="button" data-value="{{ $value }}" class="{{ $value === '60' ? 'is-selected' : '' }}">{{ $label }}</button>@endforeach
                    </div>
                </div>
                @if($canManageTests)
                    <button class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-black text-white shadow-sm hover:bg-emerald-700">Buka akses</button>
                @else
                    <span class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-black text-slate-500">Hanya dapat dilihat</span>
                @endif
            </div>
        </form>
    </section>

    <section class="test-cbt-completed overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-3 py-2.5">
            <div>
                <h2 class="text-base font-black text-slate-950">Selesai</h2>
            </div>
            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-black text-emerald-700">{{ $completedApplicants->count() }} siswa</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Siswa</th>
                        <th class="px-3 py-2">No. Pendaftaran</th>
                        <th class="px-3 py-2">Jurusan</th>
                        <th class="px-3 py-2">Mulai</th>
                        <th class="px-3 py-2">Selesai</th>
                        <th class="px-3 py-2">Nilai</th>
                        <th class="px-3 py-2">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($completedApplicants as $applicant)
                        @php
                            $result = $completedResults->get($applicant->id);
                            $session = $completedSessions->get($applicant->id);
                            $startedAt = $session?->opened_at;
                            $finishedAt = $session?->closed_at ?? $result?->updated_at;
                        @endphp
                        <tr>
                            <td class="px-3 py-2 font-bold text-slate-900">{{ $applicant->biodata?->full_name ?? 'Belum isi nama' }}</td>
                            <td class="px-3 py-2 font-semibold text-emerald-700">{{ $applicant->registration_number }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $applicant->jurusan1?->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs font-semibold text-slate-600">
                                {{ $startedAt ? $startedAt->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-3 py-2 text-xs font-semibold text-slate-600">
                                {{ $finishedAt ? $finishedAt->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-3 py-2">
                                <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-black text-sky-700">{{ $result?->score ?? 0 }}</span>
                            </td>
                            <td class="px-3 py-2 text-xs font-semibold text-slate-500">{{ $result?->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-5 text-center text-slate-400">Belum ada siswa yang menyelesaikan CBT.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const all = document.getElementById('select-all-cbt');
    const items = [...document.querySelectorAll('.cbt-applicant')];
    const count = document.getElementById('selected-cbt-count');
    const update = () => count.textContent = items.filter(item => item.checked).length;
    all?.addEventListener('change', () => { items.forEach(item => item.checked = all.checked); update(); });
    items.forEach(item => item.addEventListener('change', update));
    update();

    document.querySelectorAll('[data-cbt-duration]').forEach(picker => {
        const trigger = picker.querySelector('.cbt-duration-trigger');
        const menu = picker.querySelector('.cbt-duration-menu');
        const input = picker.querySelector('input[name="duration_minutes"]');
        const close = () => { menu.hidden = true; trigger.setAttribute('aria-expanded', 'false'); };
        trigger.addEventListener('click', () => {
            const opening = menu.hidden;
            document.querySelectorAll('.cbt-duration-menu').forEach(other => other.hidden = true);
            menu.hidden = !opening;
            trigger.setAttribute('aria-expanded', String(opening));
        });
        menu.querySelectorAll('button').forEach(option => option.addEventListener('click', () => {
            input.value = option.dataset.value;
            trigger.querySelector('b').textContent = option.textContent;
            menu.querySelectorAll('button').forEach(item => item.classList.remove('is-selected'));
            option.classList.add('is-selected');
            close();
        }));
        document.addEventListener('click', event => { if (!picker.contains(event.target)) close(); });
    });

});
</script>
@endsection

