@extends('layouts.admin')

@section('title', 'Identitas Sekolah')
@section('page_title', 'Identitas Sekolah')
@section('page_description', 'Perubahan berlaku di seluruh sistem.')

@section('content')
<form method="POST" action="{{ route('admin.identitas.save') }}" enctype="multipart/form-data" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    @csrf
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
        <div class="flex items-center gap-4">
            <img src="{{ asset($settings['school_logo']) }}" class="h-14 w-14 rounded-2xl border border-slate-200 bg-white object-contain p-1" alt="Logo sekolah">
            <div>
                <h2 class="font-black text-slate-950">Identitas Global</h2>
                <p class="mt-0.5 text-xs text-slate-500">Nama, logo, alamat, dan kontak dipakai oleh semua halaman.</p>
            </div>
        </div>
        <button class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-sm hover:bg-emerald-700">Simpan Identitas</button>
    </div>

    <div class="grid gap-4 p-5 md:grid-cols-2">
        @foreach ([
            ['school_name','Nama Lengkap Sekolah'],
            ['school_short_name','Nama Singkat Sekolah'],
            ['portal_name','Nama Sistem'],
            ['brand_name','Nama Brand Sekolah'],
            ['accreditation','Akreditasi'],
            ['contact_phone','Nomor WhatsApp'],
            ['operational_hours','Jam Operasional'],
            ['tagline','Tagline'],
        ] as [$name,$label])
            <div>
                <label class="text-xs font-black text-slate-700">{{ $label }}</label>
                <input name="{{ $name }}" value="{{ old($name, $settings[$name] ?? '') }}" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
            </div>
        @endforeach
        <div class="md:col-span-2">
            <label class="text-xs font-black text-slate-700">Alamat Sekolah</label>
            <textarea name="school_address" rows="3" required class="mt-2 w-full resize-none rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">{{ old('school_address', $settings['school_address'] ?? '') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <label class="text-xs font-black text-slate-700">Deskripsi Footer</label>
            <textarea name="footer_description" rows="3" required class="mt-2 w-full resize-none rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">{{ old('footer_description', $settings['footer_description'] ?? '') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <label class="block rounded-2xl border border-dashed border-emerald-300 bg-emerald-50 p-4 text-xs font-black text-emerald-900">
                Ganti Logo Sekolah <span class="font-medium text-emerald-700">(PNG/JPG/WebP, maks. 2 MB)</span>
                <input type="file" name="school_logo" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full text-xs font-bold text-slate-600">
            </label>
        </div>
        <div class="md:col-span-2">
            <label class="block rounded-2xl border border-dashed border-sky-300 bg-sky-50 p-4 text-xs font-black text-sky-900">
                Kop Surat Resmi untuk Formulir Cetak <span class="font-medium text-sky-700">(unggah gambar PNG/JPG/WebP, maks. 4 MB)</span>
                <input type="file" name="letterhead" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full text-xs font-bold text-slate-600">
                <span class="mt-2 block font-medium text-sky-700">Gunakan gambar kop lengkap dari sekolah. Gambar ini akan tampil apa adanya di bagian atas formulir PDF.</span>
                @if(!empty($settings['letterhead_path']))<span class="mt-2 block font-bold text-emerald-700">Kop surat resmi sudah tersimpan.</span>@endif
            </label>
        </div>
    </div>

    @if($errors->any())
        <div class="mx-5 mb-5 rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-600">{{ $errors->first() }}</div>
    @endif
</form>
@endsection
