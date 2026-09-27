@extends('layouts.admin')

@section('title', 'Kelola Soal CBT')
@section('page_title', 'Kelola Soal CBT')

@section('content')
<div class="cbt-question-bank mx-auto max-w-[1180px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="cbt-question-bank-head flex flex-col gap-3 border-b border-slate-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="interview-session-kicker">Materi tes</p>
            <div class="flex items-center gap-3"><h2 class="text-lg font-black text-slate-950">Bank soal CBT</h2><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600">{{ $cbtQuestions->total() }} soal</span></div>
            <p class="mt-1 text-xs font-semibold text-slate-500">Soal aktif akan tampil pada CBT siswa. Panitia hanya membuka akses ujian.</p>
        </div>
        <div class="cbt-question-tools flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.tes.cbt-template') }}">Template</a>
            <a href="{{ route('admin.tes.cbt-export') }}">Export</a>
            <form id="cbt-import-form" method="POST" action="{{ route('admin.tes.cbt-import') }}" enctype="multipart/form-data" class="inline-flex">
                @csrf
                <label class="inline-flex h-[34px] cursor-pointer items-center justify-center rounded-[9px] border border-sky-200 bg-sky-50 px-3 text-[10px] font-extrabold text-sky-700">Import<input id="cbt-import-file" type="file" name="file" accept=".xlsx,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="sr-only"></label>
            </form>
            <button type="button" class="cbt-add-question-trigger" onclick="document.getElementById('cbt-question-composer').open = true; document.getElementById('cbt-question-composer').scrollIntoView({behavior:'smooth', block:'center'});">Tambah soal</button>
        </div>
    </div>

    <details id="cbt-question-composer" class="cbt-question-composer border-b border-slate-100">
        <summary>Input soal manual</summary>
        <form method="POST" action="{{ route('admin.tes.cbt-question.store') }}" class="grid gap-3 p-5">
            @csrf
            <textarea name="question" required rows="2" placeholder="Tulis pertanyaan" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold">{{ old('question') }}</textarea>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach(['a','b','c','d'] as $option)<input name="option_{{ $option }}" required value="{{ old('option_'.$option) }}" placeholder="Pilihan {{ strtoupper($option) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold">@endforeach
            </div>
            <div class="flex flex-wrap items-end gap-2">
                <label class="grid gap-1 text-[10px] font-black uppercase tracking-wide text-slate-500">Kunci
                    <select name="correct_answer" required class="h-[40px] min-w-32 rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-slate-700"><option value="">Pilih</option>@foreach(['A','B','C','D'] as $answer)<option value="{{ $answer }}" @selected(old('correct_answer') === $answer)>{{ $answer }}</option>@endforeach</select>
                </label>
                <label class="flex h-[40px] items-center gap-2 rounded-xl bg-slate-50 px-3 text-xs font-bold text-slate-600"><input type="checkbox" name="status" value="1" checked class="rounded text-emerald-600"> Aktif</label>
                <button class="h-[40px] rounded-xl bg-slate-900 px-4 text-xs font-black text-white">Simpan soal</button>
            </div>
        </form>
    </details>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-500"><tr><th class="px-5 py-3">Pertanyaan</th><th class="px-4 py-3">Kunci</th><th class="px-4 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($cbtQuestions as $question)
                    <tr>
                        <td class="px-5 py-3"><div class="flex items-center gap-3"><p class="min-w-0 flex-1 truncate font-bold text-slate-900">{{ $question->question }}</p><details class="cbt-question-options"><summary>Lihat pilihan</summary><div>A. {{ $question->option_a }}<br>B. {{ $question->option_b }}<br>C. {{ $question->option_c }}<br>D. {{ $question->option_d }}</div></details></div></td>
                        <td class="px-4 py-3 font-black text-emerald-700">{{ $question->correct_answer }}</td>
                        <td class="px-4 py-3 text-xs font-bold text-slate-600">{{ $question->status ? 'Aktif' : 'Nonaktif' }}</td>
                        <td class="px-5 py-3 text-right"><form method="POST" action="{{ route('admin.tes.cbt-question.destroy', $question) }}" onsubmit="return confirm('Hapus soal ini?')">@csrf @method('DELETE')<button class="rounded-full bg-rose-50 px-3 py-1.5 text-xs font-black text-rose-700">Hapus</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-8 text-center text-sm font-semibold text-slate-400">Belum ada soal CBT.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-per-page-pagination :paginator="$cbtQuestions" />
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('cbt-import-file');
    const form = document.getElementById('cbt-import-form');
    input?.addEventListener('change', () => { if (input.files?.length) form.requestSubmit(); });
});
</script>
@endsection
