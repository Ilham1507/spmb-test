<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h3 class="font-black text-slate-900">Pilih data laporan</h3>
        <a href="{{ route($routePrefix.'laporan-eksekutif.index', array_merge($filters, ['month' => now()->format('Y-m')])) }}" class="rounded-lg bg-teal-50 px-3 py-2 text-sm font-bold text-teal-800">Pendaftaran bulan ini</a>
    </div>
    <form method="GET" action="{{ route($routePrefix.'laporan-eksekutif.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <label class="text-sm font-bold text-slate-700">Tahun ajaran<select name="academic_year_id" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5"><option value="0">Semua tahun ajaran</option>@foreach($years as $year)<option value="{{ $year->id }}" @selected(($filters['academic_year_id'] ?? 0) == $year->id)>{{ $year->name }}</option>@endforeach</select></label>
        <label class="text-sm font-bold text-slate-700">Bulan daftar<input type="month" name="month" value="{{ $filters['month'] ?? '' }}" class="mt-1.5 w-full min-w-0 rounded-xl border border-slate-200 px-3 py-2.5"></label>
        @foreach(['major_id' => ['Jurusan pilihan 1', $majors], 'wave_id' => ['Gelombang', $waves], 'path_id' => ['Jalur / beasiswa', $paths]] as $key => [$label, $options])
            <label class="text-sm font-bold text-slate-700">{{ $label }}<select name="{{ $key }}" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5"><option value="0">Semua</option>@foreach($options as $option)<option value="{{ $option->id }}" @selected(($filters[$key] ?? 0) == $option->id)>{{ $option->name }}</option>@endforeach</select></label>
        @endforeach
        <label class="text-sm font-bold text-slate-700">Status<select name="status" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5"><option value="">Semua status</option>@foreach(\App\Services\ExecutiveReportService::STATUSES as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm font-bold text-slate-700">Sekolah asal<input name="school" value="{{ $filters['school'] ?? '' }}" placeholder="Nama sekolah lengkap" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5"></label>
        <label class="text-sm font-bold text-slate-700">Nama / nomor pendaftaran<input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari siswa" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5"></label>
        <div class="flex gap-2 sm:col-span-2 xl:col-span-4"><button class="rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-bold text-white">Tampilkan laporan</button><a href="{{ route($routePrefix.'laporan-eksekutif.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Reset</a></div>
    </form>
    <p class="mt-3 text-xs leading-relaxed text-slate-500">Bulan daftar dihitung dari tanggal akun pendaftar dibuat. Angka dan Excel mengikuti filter. Data promosi terpisah: hanya mengikuti bulan dan jurusan, karena belum memiliki tahun ajaran atau status pendaftaran.</p>
</section>
