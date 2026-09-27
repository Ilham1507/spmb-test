@php($isEdit = filled($gelombang))
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-ui-select
            name="academic_year_id"
            label="Tahun Ajaran"
            :required="true"
            placeholder="Pilih tahun ajaran"
            :selected="$gelombang?->academic_year_id"
            :options="$tahunAjarans->map(fn($tahun) => ['value' => $tahun->id, 'label' => $tahun->name])->all()"
        />
    </div>
    <div>
        <label class="text-xs font-black text-slate-700">Nama Gelombang</label>
        <input name="name" value="{{ old('name', $gelombang?->name) }}" placeholder="Contoh: Gelombang 1 Tahap 1" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
    </div>
    <div>
        <label class="text-xs font-black text-slate-700">Tanggal Mulai</label>
        <input type="date" name="start_date" value="{{ old('start_date', $gelombang?->start_date ? \Carbon\Carbon::parse($gelombang->start_date)->format('Y-m-d') : '') }}" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
    </div>
    <div>
        <label class="text-xs font-black text-slate-700">Tanggal Selesai</label>
        <input type="date" name="end_date" value="{{ old('end_date', $gelombang?->end_date ? \Carbon\Carbon::parse($gelombang->end_date)->format('Y-m-d') : '') }}" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
    </div>
    <div>
        <label class="text-xs font-black text-slate-700">Kuota</label>
        <input type="number" name="quota" value="{{ old('quota', $gelombang?->quota ?? 0) }}" min="0" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
    </div>
    <div>
        <x-ui-select
            name="status"
            label="Status"
            :required="true"
            :selected="$gelombang?->status ?? 'nonaktif'"
            :options="[
                ['value' => 'aktif', 'label' => 'Aktif', 'description' => 'Gelombang ini dibuka untuk peserta.'],
                ['value' => 'nonaktif', 'label' => 'Tidak Aktif', 'description' => 'Gelombang disimpan tapi belum digunakan.'],
            ]"
        />
    </div>
</div>
