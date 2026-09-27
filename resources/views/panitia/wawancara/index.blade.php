@extends(request()->routeIs('admin.*') ? 'layouts.admin' : 'layouts.panitia')

@section('title', 'Wawancara')
@section('page_title', 'Jadwal Wawancara')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
    <div class="w-16 h-16 bg-slate-100 rounded-full mx-auto flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
    </div>
    <h3 class="font-bold text-lg text-slate-800">Modul Wawancara</h3>
    <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">Fitur ini akan tersedia pada fase pengembangan selanjutnya. Modul wawancara akan mencakup penjadwalan, input nilai wawancara, dan catatan pewawancara.</p>
</div>
@endsection
