<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-ui-select
            name="tahun_ajaran_id"
            label="Tahun Ajaran"
            :required="true"
            placeholder="Pilih tahun ajaran"
            :selected="old('tahun_ajaran_id', $jadwal?->tahun_ajaran_id ?? $tahunAjarans->firstWhere('is_active', true)?->id)"
            :options="$tahunAjarans->map(fn($tahun) => ['value' => $tahun->id, 'label' => $tahun->name])->all()"
        />
    </div>
    <div>
        <label class="text-xs font-black text-slate-700">Nama kegiatan <span class="text-rose-600">*</span></label>
        <input name="kegiatan" aria-label="Nama kegiatan" value="{{ old('kegiatan', $jadwal?->kegiatan ?? 'Tes SPMB') }}" required maxlength="150" placeholder="Contoh: Tes SPMB" class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
    </div>
    <div>
        <label class="text-xs font-black text-slate-700">Tanggal & jam mulai <span class="text-rose-600">*</span> <span class="font-medium">(WIB)</span></label>
        <input type="datetime-local" name="tanggal_mulai" value="{{ old('tanggal_mulai', $jadwal?->tanggal_mulai ? \Carbon\Carbon::parse($jadwal->tanggal_mulai)->format('Y-m-d\TH:i') : '') }}" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
    </div>
    <div>
        <label class="text-xs font-black text-slate-700">Tanggal & jam selesai <span class="font-medium">(opsional, WIB)</span></label>
        <input type="datetime-local" name="tanggal_selesai" value="{{ old('tanggal_selesai', $jadwal?->tanggal_selesai ? \Carbon\Carbon::parse($jadwal->tanggal_selesai)->format('Y-m-d\TH:i') : '') }}" class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
    </div>
    <div class="sm:col-span-2">
        <label class="text-xs font-black text-slate-700">Catatan untuk siswa <span class="font-medium">(opsional)</span></label>
        <textarea name="keterangan" rows="3" class="mt-2 w-full resize-none rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">{{ old('keterangan', $jadwal?->keterangan) }}</textarea>
    </div>
    <label class="sm:col-span-2 flex cursor-pointer items-start gap-3 rounded-2xl border border-teal-200 bg-teal-50 p-4">
        <input type="hidden" name="available_for_student_selection" value="0">
        <input type="checkbox" name="available_for_student_selection" value="1" @checked(old('available_for_student_selection', $jadwal?->available_for_student_selection ?? false)) class="mt-0.5 rounded border-teal-300 text-teal-700 focus:ring-teal-500">
        <span><b class="block text-sm text-teal-900">Buka jadwal ini untuk dipilih siswa</b><small class="mt-1 block text-xs font-semibold leading-relaxed text-teal-700">Centang jika tanggal tes sudah pasti. Jika tidak dicentang, jadwal disimpan tetapi belum ditawarkan kepada siswa.</small></span>
    </label>
</div>
