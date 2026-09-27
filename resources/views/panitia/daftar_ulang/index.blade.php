@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.panitia')

@section('title', 'Daftar Ulang')
@section('page_title', 'Administrasi Daftar Ulang')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-slate-100">
        <h3 class="font-bold text-slate-900">Siswa Lulus Seleksi</h3>
        <p class="text-sm text-slate-500 mt-1">Pendaftar yang sudah lulus seleksi dan menunggu proses daftar ulang fisik/pemberkasan final.</p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100">
                    <th class="p-4 pl-6">Nama & No. Pendaftaran</th>
                    <th class="p-4">Jurusan Diterima</th>
                    <th class="p-4 pr-6 text-right">Aksi Daftar Ulang</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($pendaftars as $p)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="p-4 pl-6">
                        <p class="font-semibold text-slate-900">{{ $p->biodata?->full_name ?? '-' }}</p>
                        <p class="text-xs text-slate-400 font-mono">{{ $p->registration_number ?? '' }}</p>
                    </td>
                    <td class="p-4">
                        <span class="font-medium text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100">{{ $p->jurusan1?->name ?? '-' }}</span>
                    </td>
                    <td class="p-4 pr-6 text-right">
                        <form method="POST" action="{{ route((request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . 'daftar_ulang.process', $p->id) }}" class="inline-block text-left">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="completed">
                            <div class="flex items-center justify-end gap-2">
                                <input type="text" name="notes" placeholder="Catatan/Keterangan..." class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 w-40 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                                <button type="submit" onclick="return confirm('Proses daftar ulang untuk siswa ini?')"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition-colors shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                                    Sah, Selesai!
                                </button>
                            </div>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="p-8 text-center text-slate-500 font-medium">Belum ada siswa yang lulus / siap daftar ulang.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($pendaftars->hasPages())
    <div class="p-4 border-t border-slate-100">
        <x-per-page-pagination :paginator="$pendaftars" />
    </div>
    @endif
</div>
@endsection
