<section class="grid gap-5 xl:grid-cols-2">
    @foreach(['Jalur pendaftaran & beasiswa' => $pathSummary, 'Gelombang pendaftaran' => $waveSummary, 'Pendaftaran per bulan' => $monthSummary, 'Sekolah asal terbanyak' => $schoolSummary] as $title => $groups)
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-black text-slate-900">{{ $title }}</h3>
            @if($title === 'Jalur pendaftaran & beasiswa')<p class="mt-1 text-xs text-slate-500">{{ $scholarshipRows->count() }} pendaftar pada jalur bernama Beasiswa. Bukan penetapan atau pencairan beasiswa.</p>@endif
            <p class="mt-2 text-xs text-slate-500">Klik kelompok untuk melihat nama siswa.</p>
            <div class="mt-3 divide-y divide-slate-100">
                @forelse($groups as $group)
                    <details class="py-3">
                        <summary class="flex cursor-pointer items-center justify-between gap-3 text-sm"><span class="font-bold text-slate-700">{{ $title === 'Pendaftaran per bulan' && $group->name !== 'Tidak diketahui' ? \Carbon\Carbon::createFromFormat('!Y-m', $group->name)->locale('id')->translatedFormat('F Y') : $group->name }}</span><span class="shrink-0 rounded-full bg-teal-50 px-3 py-1 font-black text-teal-800">{{ $group->applicants }} siswa</span></summary>
                        <p class="mt-2 text-xs text-slate-500">{{ $group->accepted }} diterima (termasuk {{ $group->re_registered }} daftar ulang)</p>
                        <ul class="mt-3 space-y-2">@foreach($group->members->take(100) as $member)<li class="rounded-lg bg-slate-50 px-3 py-2 text-sm"><b>{{ $member['name'] }}</b><span class="block text-xs text-slate-500">{{ $member['number'] }} · {{ $member['major'] }} · {{ $member['statusLabel'] }}</span></li>@endforeach</ul>
                        @if($group->members->count() > 100)<p class="mt-2 text-xs text-slate-500">Menampilkan 100 nama. Seluruh nama tersedia di Excel atau tabel daftar siswa dengan filter.</p>@endif
                    </details>
                @empty<p class="py-5 text-sm text-slate-500">Tidak ada data sesuai filter.</p>@endforelse
            </div>
        </article>
    @endforeach
</section>
<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap justify-between gap-3"><div><h3 class="text-lg font-black text-slate-900">Minat jurusan saat promosi</h3><p class="mt-1 text-xs text-slate-500">{{ $interests->count() }} peminat. Ini belum berarti sudah mendaftar. Data hanya mengikuti filter bulan dan jurusan.</p></div></div>
    <div class="mt-4 grid gap-3 md:grid-cols-2">
        @forelse($promotionSummary as $group)
            <details class="rounded-xl border border-slate-200 p-4"><summary class="flex cursor-pointer justify-between gap-3 text-sm font-bold"><span>{{ $group->name }}</span><span class="text-teal-700">{{ $group->total }} peminat</span></summary><ul class="mt-3 space-y-2">@foreach($group->members->take(100) as $interest)<li class="rounded-lg bg-slate-50 p-3 text-sm"><b>{{ $interest->full_name }}</b><span class="block text-xs text-slate-500">{{ $interest->school_name }} · {{ $interest->submitted_at?->format('d/m/Y') }}</span></li>@endforeach</ul>@if($group->total > 100)<p class="mt-2 text-xs text-slate-500">Seluruh nama tersedia di sheet Minat Promosi.</p>@endif</details>
        @empty<p class="text-sm text-slate-500">Belum ada data minat promosi sesuai filter.</p>@endforelse
    </div>
</section>
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <header class="p-5"><h3 class="text-lg font-black text-slate-900">Daftar siswa sesuai filter</h3><p class="mt-1 text-xs text-slate-500">{{ $detailRows->total() }} siswa. Jurusan diterima mengikuti keputusan seleksi. Excel memuat seluruh baris, bukan hanya halaman ini.</p></header>
    <div class="overflow-x-auto"><table class="w-full min-w-[1050px] text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-500"><tr>@foreach(['Siswa', 'Tanggal daftar', 'Jalur', 'Gelombang', 'Jurusan pilihan 1', 'Jurusan diterima', 'Sekolah asal', 'Status'] as $label)<th class="px-4 py-3">{{ $label }}</th>@endforeach</tr></thead><tbody class="divide-y divide-slate-100">@forelse($detailRows as $row)<tr><td class="px-4 py-3"><b>{{ $row['name'] }}</b><span class="block text-xs text-slate-500">{{ $row['number'] }}</span></td><td class="px-4 py-3">{{ $row['date']?->format('d/m/Y') }}</td><td class="px-4 py-3">{{ $row['path'] }}</td><td class="px-4 py-3">{{ $row['wave'] }}</td><td class="px-4 py-3">{{ $row['major'] }}</td><td class="px-4 py-3">{{ $row['acceptedMajor'] ?: '—' }}</td><td class="px-4 py-3">{{ $row['school'] }}</td><td class="px-4 py-3 font-bold text-teal-800">{{ $row['statusLabel'] }}</td></tr>@empty<tr><td colspan="8" class="p-8 text-center text-slate-500">Tidak ada siswa sesuai filter.</td></tr>@endforelse</tbody></table></div>
    <div class="p-5">{{ $detailRows->links() }}</div>
</section>
