@extends('layouts.admin')

@section('title','Landing Builder')
@section('page_title','Kelola Landing Page')
@section('page_description','Atur isi dan urutan tampilan publik.')

@section('content')
<form method="POST" action="{{ route('admin.landing.save') }}" x-data='landingBuilder(@json($sections))' class="space-y-4">
    @csrf
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-black text-slate-950">Bagian Landing Page</h2>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600" x-text="sections.length + ' data'"></span>
                </div>
                <p class="mt-0.5 text-xs text-slate-500">Susunan, teks, dan status tampil dikelola manual oleh admin.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="addSection" class="rounded-xl bg-sky-100 px-4 py-2.5 text-xs font-black text-sky-700">+ Tambah Bagian</button>
                <a href="{{ route('home') }}" target="_blank" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black text-slate-600">Pratinjau</a>
                <button class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white shadow-sm hover:bg-emerald-700">Terbitkan</button>
            </div>
        </div>

        <div class="grid gap-4 p-5 lg:grid-cols-2">
            <template x-for="(section,index) in sections" :key="section.id">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <input type="hidden" :name="`sections[${index}][id]`" :value="section.id">
                    <input type="hidden" :name="`sections[${index}][type]`" :value="section.type">
                    <input type="hidden" :name="`sections[${index}][enabled]`" value="0">
                    <div class="mb-4 flex items-center gap-3 border-b border-slate-100 pb-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-black text-slate-500" x-text="index+1"></span>
                        <div class="min-w-0 flex-1">
                            <strong class="block truncate text-slate-950" x-text="sectionName(section)"></strong>
                            <small class="text-slate-500" x-text="section.layout==='full' ? 'Lebar penuh' : 'Setengah baris'"></small>
                        </div>
                        <button type="button" @click="move(index,-1)" :disabled="index===0" class="h-9 w-9 rounded-lg border border-slate-200 text-xs font-black text-slate-600 disabled:opacity-30" title="Naik">Up</button>
                        <button type="button" @click="move(index,1)" :disabled="index===sections.length-1" class="h-9 w-11 rounded-lg border border-slate-200 text-xs font-black text-slate-600 disabled:opacity-30" title="Turun">Down</button>
                        <button x-show="section.type==='custom'" type="button" @click="remove(index)" class="h-9 rounded-lg bg-rose-100 px-3 text-xs font-black text-rose-700">Hapus</button>
                    </div>

                    <div class="grid gap-3">
                        <div class="flex flex-wrap items-end gap-3">
                            <label class="min-w-[180px] flex-1 text-xs font-black text-slate-700">Label
                                <input :name="`sections[${index}][eyebrow]`" x-model="section.eyebrow" class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                            </label>
                            <div class="relative w-44 text-xs font-black text-slate-700" @click.outside="section.layoutMenu=false">Lebar
                                <input type="hidden" :name="`sections[${index}][layout]`" :value="section.layout">
                                <button type="button" @click="section.layoutMenu=!section.layoutMenu" class="mt-2 flex w-full items-center justify-between rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-black text-slate-800 transition hover:border-emerald-300 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                                    <span x-text="section.layout==='full' ? 'Penuh' : 'Setengah'"></span>
                                    <svg class="h-5 w-5 text-slate-400 transition-transform" :class="section.layoutMenu ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-cloak x-show="section.layoutMenu" x-transition class="absolute z-40 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white p-1 shadow-xl">
                                    <button type="button" @click="section.layout='full'; section.layoutMenu=false" class="flex w-full items-center justify-between rounded-xl px-4 py-3 text-left text-sm font-black text-slate-700 hover:bg-emerald-50 hover:text-emerald-700">
                                        Penuh
                                        <span x-show="section.layout==='full'" class="text-xs font-black text-emerald-600">Dipilih</span>
                                    </button>
                                    <button type="button" @click="section.layout='half'; section.layoutMenu=false" class="flex w-full items-center justify-between rounded-xl px-4 py-3 text-left text-sm font-black text-slate-700 hover:bg-emerald-50 hover:text-emerald-700">
                                        Setengah
                                        <span x-show="section.layout==='half'" class="text-xs font-black text-emerald-600">Dipilih</span>
                                    </button>
                                </div>
                            </div>
                            <label class="flex h-12 items-center gap-2 rounded-2xl bg-emerald-50 px-4 text-xs font-black text-emerald-800">
                                <input type="checkbox" :name="`sections[${index}][enabled]`" value="1" x-model="section.enabled" class="rounded border-emerald-300 text-emerald-600">
                                Tampilkan
                            </label>
                        </div>
                        <label class="text-xs font-black text-slate-700">Judul
                            <input :name="`sections[${index}][title]`" x-model="section.title" required class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                        </label>
                        <label class="text-xs font-black text-slate-700">Keterangan
                            <textarea :name="`sections[${index}][body]`" x-model="section.body" rows="3" class="mt-2 w-full resize-none rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100"></textarea>
                        </label>
                        <label x-show="['highlights','flow','custom'].includes(section.type)" class="text-xs font-black text-slate-700">Poin <span class="font-medium text-slate-500">(Judul | Keterangan)</span>
                            <textarea :name="`sections[${index}][items_text]`" x-model="section.items_text" rows="4" class="mt-2 w-full resize-none rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 font-mono text-xs font-bold focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100"></textarea>
                        </label>
                        <input x-show="!['highlights','flow','custom'].includes(section.type)" type="hidden" :name="`sections[${index}][items_text]`" value="">
                    </div>
                </article>
            </template>
        </div>
    </section>

    @if($errors->any())
        <div class="rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-600">{{ $errors->first() }}</div>
    @endif
</form>

<script>
function landingBuilder(initial) {
    return {
        sections: initial.map(s => ({...s, type: s.type || s.id, items_text: (s.items || []).join('\n'), layoutMenu: false})),
        labels: {
            hero: 'Pembuka',
            highlights: 'Keunggulan',
            jurusan: 'Daftar Jurusan',
            flow: 'Alur Pendaftaran',
            contact: 'Kontak',
            cta: 'Ajakan Daftar',
            custom: 'Bagian Tambahan',
        },
        sectionName(section) {
            return this.labels[section.type] || 'Bagian Tambahan';
        },
        move(index, step) {
            const target = index + step;
            if (target < 0 || target >= this.sections.length) return;
            [this.sections[index], this.sections[target]] = [this.sections[target], this.sections[index]];
        },
        remove(index) {
            if (confirm('Hapus bagian ini?')) this.sections.splice(index, 1);
        },
        addSection() {
            this.sections.push({
                id: 'custom-' + Date.now(),
                type: 'custom',
                enabled: true,
                layout: 'full',
                eyebrow: 'Informasi',
                title: 'Judul bagian baru',
                body: 'Tulis keterangan singkat yang mudah dipahami.',
                items_text: 'Poin pertama | Keterangan singkat',
                layoutMenu: false,
            });
            setTimeout(() => window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }), 50);
        },
    };
}
</script>
@endsection
