@extends('layouts.school')

@section('title', 'FAQ')

@section('content')
<div class="bg-[#effcf8]">
    <section class="relative overflow-hidden bg-[#073f39] px-5 py-16 text-white">
        <div class="relative mx-auto max-w-6xl">
            <span class="inline-flex rounded-full bg-cyan-100 px-4 py-2 text-xs font-black uppercase tracking-widest text-emerald-950">Pusat Bantuan</span>
            <h1 class="mt-5 max-w-3xl text-4xl font-black leading-tight md:text-5xl">Pertanyaan yang sering ditanyakan</h1>
            <p class="mt-4 max-w-2xl text-base leading-relaxed text-emerald-50/80 md:text-lg">Temukan jawaban singkat untuk pertanyaan yang sering muncul.</p>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-5 py-12">
        <div class="space-y-3">
            @forelse($faqs as $faq)
                <details class="group rounded-3xl border border-emerald-100 bg-white p-5 shadow-sm [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex cursor-pointer items-center justify-between gap-4 font-black text-slate-950">
                        <span>{{ $faq->pertanyaan }}</span>
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-cyan-50 text-emerald-700 transition group-open:rotate-180">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </span>
                    </summary>
                    <p class="mt-4 text-sm leading-relaxed text-slate-600">{{ $faq->jawaban }}</p>
                </details>
            @empty
                <div class="rounded-[2rem] border border-amber-200 bg-amber-50 p-6 text-amber-900">
                    <h2 class="text-xl font-black">FAQ belum tersedia</h2>
                    <p class="mt-2 text-sm font-semibold">Pertanyaan dan jawaban akan segera tersedia.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
