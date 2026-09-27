@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.bendahara')

@section('title', 'Biaya Pendaftaran')
@section('page_title', 'Biaya Pendaftaran')
@section('page_description', 'Atur biaya formulir pendaftaran untuk tahun ajaran aktif.')

@section('content')
@php($isBendahara = request()->routeIs('bendahara.*'))
<section class="mx-auto max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h2 class="font-black text-slate-950">Biaya Formulir Pendaftaran</h2>
        <p class="mt-1 text-xs text-slate-500">Nominal ini dipakai saat tagihan formulir baru dibuat. Tagihan yang sudah ada tidak berubah.</p>
    </div>
    <form method="POST" action="{{ route($routePrefix.'keuangan.biaya-pendaftaran.save') }}" class="space-y-5 p-5">
        @csrf
        <div class="rounded-xl px-4 py-3 text-sm font-semibold {{ $isBendahara ? 'bg-amber-50 text-amber-800' : 'bg-blue-50 text-blue-800' }}">
            Tahun ajaran aktif: <strong>{{ $tahunAjaran?->name ?? '-' }}</strong>
        </div>
        <div>
            <label for="biaya_pendaftaran" class="text-xs font-black text-slate-700">Biaya pendaftaran</label>
            <div class="mt-2 flex items-center overflow-hidden rounded-2xl border-2 border-slate-200 bg-slate-50 {{ $isBendahara ? 'focus-within:border-amber-600 focus-within:ring-4 focus-within:ring-amber-100' : 'focus-within:border-blue-700 focus-within:ring-4 focus-within:ring-blue-100' }}">
                <span class="px-4 text-sm font-black text-slate-500">Rp</span>
                <input id="biaya_pendaftaran" type="number" inputmode="numeric" name="biaya_pendaftaran" value="{{ old('biaya_pendaftaran', $pengaturan?->biaya_pendaftaran ?? 0) }}" min="0" required class="w-full border-0 bg-transparent px-2 py-3 text-sm font-bold focus:outline-none focus:ring-0">
            </div>
            @error('biaya_pendaftaran')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end">
            <button class="btn-primary rounded-xl px-5 py-3 text-sm font-black text-white">Simpan Biaya</button>
        </div>
    </form>
</section>
@endsection
