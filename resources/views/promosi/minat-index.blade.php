@extends(auth()->user()?->hasRole('kepala_sekolah') ? 'layouts.kepala-sekolah' : (auth()->user()?->hasRole('panitia') ? 'layouts.panitia' : (auth()->user()?->hasRole('bendahara') ? 'layouts.bendahara' : 'layouts.admin')))

@section('title', 'Minat Promosi Siswa')
@section('page_title', 'Minat Promosi Siswa')
@section('page_description', 'Data siswa yang tertarik saat kegiatan promosi sekolah.')

@section('content')
@php
    $prefix = auth()->user()?->hasRole('kepala_sekolah') ? 'kepala-sekolah.' : (auth()->user()?->hasRole('panitia') ? 'panitia.' : (auth()->user()?->hasRole('bendahara') ? 'bendahara.' : 'admin.'));
    $canDelete = auth()->user()?->hasRole('admin');
    $exportColor = auth()->user()?->hasRole('panitia') ? 'bg-violet-700 hover:bg-violet-800' : (auth()->user()?->hasRole('bendahara') ? 'bg-amber-600 hover:bg-amber-700' : (auth()->user()?->hasRole('kepala_sekolah') ? 'bg-teal-700 hover:bg-teal-800' : 'bg-blue-700 hover:bg-blue-800'));
@endphp
<div class="mx-auto max-w-[1280px] space-y-5">
    <section class="overflow-hidden rounded-3xl bg-gradient-to-r from-teal-800 to-blue-900 px-6 py-7 text-white shadow-lg sm:px-8">
        <p class="text-xs font-black uppercase tracking-[.16em] text-teal-100">Database promosi</p>
        <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><h2 class="text-2xl font-black sm:text-3xl">Siswa yang berminat</h2><p class="mt-2 text-sm text-teal-50">{{ $interests->total() }} data minat dari kegiatan promosi.</p></div><a href="{{ route('promosi.minat.create') }}" target="_blank" class="inline-flex w-fit items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-black text-teal-800 shadow-sm hover:bg-teal-50">Buka formulir promosi ↗</a></div>
    </section>
    <section class="admin-card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <form method="GET" class="flex flex-1 flex-col gap-2 sm:flex-row"><input name="search" value="{{ request('search') }}" class="admin-input flex-1" placeholder="Cari nama, WhatsApp, SMP/MTs, atau jurusan"><button class="rounded-xl bg-teal-700 px-5 py-3 text-sm font-black text-white hover:bg-teal-800">Cari</button>@if(request('search'))<a href="{{ route($prefix.'promosi-minat.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 text-center text-sm font-black text-slate-600">Reset</a>@endif</form>
            <a href="{{ route($prefix.'promosi-minat.export', request()->only('search')) }}" class="inline-flex items-center justify-center gap-2 rounded-xl {{ $exportColor }} px-5 py-3 text-sm font-black text-white shadow-sm">↓ Export Excel</a>
        </div>
        <div class="admin-table-wrap"><table class="admin-table min-w-[760px]"><thead><tr><th>Siswa</th><th>Kontak</th><th>SMP/MTs</th><th>Jurusan diminati</th><th>Diisi</th>@if($canDelete)<th class="text-right">Aksi</th>@endif</tr></thead><tbody>@forelse($interests as $interest)<tr><td><p class="font-black text-slate-950">{{ $interest->full_name }}</p></td><td><p class="font-bold text-slate-700">{{ $interest->student_phone }}</p></td><td class="font-semibold text-slate-700">{{ $interest->school_name }}</td><td>{{ $interest->major_interest ?: 'Belum menentukan' }}</td><td><p class="font-bold text-slate-700">{{ $interest->submitted_at?->format('d M Y') }}</p><p class="text-xs text-slate-400">{{ $interest->submitted_at?->format('H:i') }} WIB</p></td>@if($canDelete)<td class="text-right"><form method="POST" action="{{ route('admin.promosi-minat.destroy', $interest) }}" onsubmit="return confirm('Hapus data minat dari {{ addslashes($interest->full_name) }}?')">@csrf @method('DELETE')<button class="text-sm font-black text-rose-600 hover:text-rose-800">Hapus</button></form></td>@endif</tr>@empty<tr><td colspan="{{ $canDelete ? 6 : 5 }}" class="py-10 text-center text-slate-500">Belum ada data minat promosi.</td></tr>@endforelse</tbody></table></div>
        <div class="border-t border-slate-100 p-4">{{ $interests->links() }}</div>
    </section>
</div>
@endsection
