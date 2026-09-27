@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.bendahara')

@section('title', 'Rekening Resmi')
@section('page_title', 'Rekening Pembayaran')

@section('content')
<div class="space-y-5">
    @if(session('success'))
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-1">
            <span class="text-xs font-black uppercase tracking-[0.2em] text-amber-600">Rekening resmi sekolah</span>
            <h2 class="text-xl font-black text-slate-950">Tambah Rekening Pembayaran</h2>
            <p class="text-sm text-slate-500">Rekening aktif akan tampil di halaman pembayaran peserta. Pastikan hanya rekening sekolah yang dimasukkan.</p>
        </div>

        <form method="POST" action="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'bendahara.') . 'rekening.store') }}" class="mt-5 grid gap-4 lg:grid-cols-[1fr_1fr_1fr_auto]">
            @csrf
            <div>
                <label for="nama_bank" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Bank / Metode</label>
                <input id="nama_bank" name="nama_bank" value="{{ old('nama_bank') }}" placeholder="Contoh: BSI / BCA / QRIS"
                       class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                <x-input-error :messages="$errors->get('nama_bank')" class="mt-1" />
            </div>
            <div>
                <label for="nomor_rekening" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Nomor Rekening / ID QRIS</label>
                <input id="nomor_rekening" name="nomor_rekening" value="{{ old('nomor_rekening') }}" placeholder="Nomor rekening resmi"
                       class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                <x-input-error :messages="$errors->get('nomor_rekening')" class="mt-1" />
            </div>
            <div>
                <label for="atas_nama" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Atas Nama</label>
                <input id="atas_nama" name="atas_nama" value="{{ old('atas_nama') }}" placeholder="Nama pemilik rekening"
                       class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                <x-input-error :messages="$errors->get('atas_nama')" class="mt-1" />
            </div>
            <div class="flex flex-col justify-end gap-3">
                <label class="inline-flex items-center gap-2 text-sm font-bold text-slate-700">
                    <input type="checkbox" name="status" value="1" checked class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400">
                    Aktif
                </label>
                <button type="submit" class="rounded-2xl bg-amber-500 px-5 py-3 text-sm font-black text-white shadow-lg shadow-amber-200 transition hover:bg-amber-600">
                    Simpan
                </button>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5">
            <h3 class="text-lg font-black text-slate-900">Daftar Rekening</h3>
            <p class="mt-1 text-sm text-slate-500">Nonaktifkan rekening lama agar tidak tampil di halaman peserta.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="p-4 pl-6">Status</th>
                        <th class="p-4">Bank / Metode</th>
                        <th class="p-4">Nomor</th>
                        <th class="p-4">Atas Nama</th>
                        <th class="p-4 pr-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rekenings as $rekening)
                        @php $updateFormId = 'rekening-update-' . $rekening->id; @endphp
                        <tr class="align-top hover:bg-amber-50/30">
                            <td class="p-4 pl-6">
                                <form id="{{ $updateFormId }}" method="POST" action="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'bendahara.') . 'rekening.update', $rekening) }}">
                                    @csrf
                                    @method('PATCH')
                                </form>
                                <label class="inline-flex items-center gap-2 rounded-full {{ $rekening->status ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }} px-3 py-1.5 text-xs font-black">
                                    <input form="{{ $updateFormId }}" type="checkbox" name="status" value="1" @checked($rekening->status) class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-400">
                                    {{ $rekening->status ? 'Aktif' : 'Nonaktif' }}
                                </label>
                            </td>
                            <td class="p-4">
                                <input form="{{ $updateFormId }}" name="nama_bank" value="{{ old('nama_bank', $rekening->nama_bank) }}"
                                       class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 font-semibold text-slate-700 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                            </td>
                            <td class="p-4">
                                <input form="{{ $updateFormId }}" name="nomor_rekening" value="{{ old('nomor_rekening', $rekening->nomor_rekening) }}"
                                       class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 font-semibold text-slate-700 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                            </td>
                            <td class="p-4">
                                <input form="{{ $updateFormId }}" name="atas_nama" value="{{ old('atas_nama', $rekening->atas_nama) }}"
                                       class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 font-semibold text-slate-700 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-100">
                            </td>
                            <td class="p-4 pr-6">
                                <div class="flex justify-end gap-2">
                                    <button form="{{ $updateFormId }}" type="submit" class="rounded-2xl bg-slate-900 px-4 py-2.5 text-xs font-black text-white transition hover:bg-slate-700">
                                        Update
                                    </button>
                                    <form method="POST" action="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'bendahara.') . 'rekening.toggle', $rekening) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-2xl {{ $rekening->status ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' }} px-4 py-2.5 text-xs font-black transition">
                                            {{ $rekening->status ? 'Matikan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'bendahara.') . 'rekening.destroy', $rekening) }}" onsubmit="return confirm('Hapus rekening ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-2xl bg-rose-50 px-4 py-2.5 text-xs font-black text-rose-700 transition hover:bg-rose-100">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-sm font-semibold text-slate-400">
                                Belum ada rekening resmi. Tambahkan rekening sekolah dulu agar opsi transfer tampil di halaman peserta.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-per-page-pagination :paginator="$rekenings" />
    </div>
</div>
@endsection
