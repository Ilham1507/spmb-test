@extends('layouts.admin')

@section('title', 'Pertanyaan Wawancara')
@section('page_title', 'Pertanyaan Wawancara')

@section('content')
<div class="interview-manager mx-auto max-w-[1040px] space-y-4">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <p class="interview-session-kicker">Panduan panitia</p>
            <h2 class="text-lg font-black text-slate-950">Pertanyaan wawancara orang tua</h2>
            <p class="mt-1 text-xs font-semibold text-slate-500">Pertanyaan aktif muncul otomatis ketika panitia memilih peserta wawancara.</p>
        </div>
        <form method="POST" action="{{ route('admin.tes.interview-question.store') }}" class="grid gap-3 p-5 md:grid-cols-[1fr_auto_auto] md:items-end">
            @csrf
            <label class="grid gap-1 text-[10px] font-black uppercase tracking-wide text-slate-500">Pertanyaan
                <input name="question" required value="{{ old('question') }}" placeholder="Contoh: Bagaimana dukungan keluarga terhadap pilihan jurusan anak?" class="h-10 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-700">
            </label>
            <label class="flex h-10 items-center gap-2 rounded-xl bg-slate-50 px-3 text-xs font-bold text-slate-600"><input type="checkbox" name="status" value="1" checked class="rounded text-emerald-600"> Aktif</label>
            <button class="h-10 rounded-xl bg-slate-900 px-4 text-xs font-black text-white">Tambah pertanyaan</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3"><h3 class="text-base font-black text-slate-950">Daftar pertanyaan</h3><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600">{{ $interviewQuestions->total() }} item</span></div>
        <div class="divide-y divide-slate-100">
            @forelse($interviewQuestions as $question)
                <form method="POST" action="{{ route('admin.tes.interview-question.update', $question) }}" class="interview-manager-row grid gap-3 px-5 py-3 md:grid-cols-[36px_1fr_auto_auto] md:items-center">
                    @csrf @method('PATCH')
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-violet-50 text-xs font-black text-violet-700">{{ $question->sort_order }}</span>
                    <input name="question" value="{{ $question->question }}" required class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700">
                    <label class="flex h-10 items-center gap-2 rounded-xl bg-slate-50 px-3 text-xs font-bold text-slate-600"><input type="checkbox" name="status" value="1" @checked($question->status) class="rounded text-emerald-600"> Aktif</label>
                    <div class="flex gap-2"><button class="h-10 rounded-xl bg-slate-100 px-3 text-xs font-black text-slate-700">Simpan</button><button form="delete-question-{{ $question->id }}" class="h-10 rounded-xl bg-rose-50 px-3 text-xs font-black text-rose-700">Hapus</button></div>
                </form>
                <form id="delete-question-{{ $question->id }}" method="POST" action="{{ route('admin.tes.interview-question.destroy', $question) }}" onsubmit="return confirm('Hapus pertanyaan ini?')" class="hidden">@csrf @method('DELETE')</form>
            @empty
                <p class="p-8 text-center text-sm font-semibold text-slate-400">Belum ada pertanyaan wawancara.</p>
            @endforelse
        </div>
        <x-per-page-pagination :paginator="$interviewQuestions" />
    </section>
</div>
@endsection
