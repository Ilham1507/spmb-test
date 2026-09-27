<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="text-xs font-black text-slate-700">Kuota</label>
        <input type="number" name="quota" value="{{ old('quota', $jurusan?->quota ?? 0) }}" min="0" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
    </div>
    <div class="sm:col-span-2">
        <label class="text-xs font-black text-slate-700">Nama Jurusan</label>
        <input name="name" value="{{ old('name', $jurusan?->name) }}" placeholder="Tulis nama lengkap jurusan" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
    </div>
    <div class="sm:col-span-2">
        <label class="text-xs font-black text-slate-700">Target lulusan / deskripsi singkat</label>
        <textarea name="description" rows="3" placeholder="Contoh: Lulusan siap bekerja di bidang tata boga, membuka usaha, atau melanjutkan kuliah." class="mt-2 w-full resize-none rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">{{ old('description', $jurusan?->description) }}</textarea>
    </div>
    <div>
        <x-ui-select
            name="status"
            label="Status"
            :required="true"
            :selected="$jurusan?->status ?? 'aktif'"
            :options="[
                ['value' => 'aktif', 'label' => 'Aktif'],
                ['value' => 'nonaktif', 'label' => 'Tidak Aktif'],
            ]"
        />
    </div>
    <div class="sm:col-span-2">
    </div>
    <div class="sm:col-span-2">
        <label class="block rounded-2xl border border-dashed border-emerald-300 bg-emerald-50 p-4 text-xs font-black text-emerald-900">
            Logo Jurusan <span class="font-medium text-emerald-700">(PNG/JPG/WebP, maks. 2 MB)</span>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full text-xs font-bold text-slate-600">
        </label>
        @if($jurusan?->logo_path)
            <img src="{{ asset($jurusan->logo_path) }}" class="mt-3 h-14 w-14 rounded-xl border border-slate-200 bg-white object-contain p-1" alt="Logo {{ $jurusan->name }}">
        @endif
    </div>
</div>
