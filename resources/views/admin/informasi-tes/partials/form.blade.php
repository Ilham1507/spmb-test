<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-ui-select
            name="tahun_ajaran_id"
            label="Tahun Ajaran"
            :required="true"
            placeholder="Pilih tahun ajaran"
            :selected="$jadwal?->tahun_ajaran_id"
            :options="$tahunAjarans->map(fn($tahun) => ['value' => $tahun->id, 'label' => $tahun->name])->all()"
        />
    </div>
    <div>
        <label class="text-xs font-black text-slate-700">Nama Kegiatan</label>
        <input name="kegiatan" value="{{ old('kegiatan', $jadwal?->kegiatan) }}" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
    </div>
    <div>
        <label class="text-xs font-black text-slate-700">Tanggal & Jam Mulai</label>
        <input type="datetime-local" name="tanggal_mulai" value="{{ old('tanggal_mulai', $jadwal?->tanggal_mulai ? \Carbon\Carbon::parse($jadwal->tanggal_mulai)->format('Y-m-d\TH:i') : '') }}" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
    </div>
    <div>
        <label class="text-xs font-black text-slate-700">Tanggal & Jam Selesai</label>
        <input type="datetime-local" name="tanggal_selesai" value="{{ old('tanggal_selesai', $jadwal?->tanggal_selesai ? \Carbon\Carbon::parse($jadwal->tanggal_selesai)->format('Y-m-d\TH:i') : '') }}" class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
    </div>
    <div class="sm:col-span-2">
        <label class="text-xs font-black text-slate-700">Keterangan</label>
        <textarea name="keterangan" rows="3" class="mt-2 w-full resize-none rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">{{ old('keterangan', $jadwal?->keterangan) }}</textarea>
    </div>
    <label class="sm:col-span-2 flex cursor-pointer items-start gap-3 rounded-2xl border border-teal-200 bg-teal-50 p-4">
        <input type="hidden" name="available_for_student_selection" value="0">
        <input type="checkbox" name="available_for_student_selection" value="1" @checked(old('available_for_student_selection', $jadwal?->available_for_student_selection ?? false)) class="mt-0.5 rounded border-teal-300 text-teal-700 focus:ring-teal-500">
        <span><b class="block text-sm text-teal-900">Tawarkan sebagai pilihan tanggal tes siswa</b><small class="mt-1 block text-xs font-semibold leading-relaxed text-teal-700">Aktifkan hanya untuk sesi Tes SPMB yang sudah pasti. Siswa memilih salah satu sesi ini sebelum mengirim formulir.</small></span>
    </label>
</div>
