@extends('layouts.admin')

@section('title', 'Berita Landing')
@section('page_title', 'Berita Landing Page')

@section('content')
<style>
.landing-news-select{position:relative;margin-top:6px}.landing-news-select-button{display:flex;width:100%;align-items:center;justify-content:space-between;border:1px solid #cbd5e1;border-radius:10px;padding:10px 12px;background:#fff;color:#173d6b;font-size:13px;font-weight:700;text-align:left}.landing-news-select-button:focus{border-color:#0b3b83;box-shadow:0 0 0 3px #e8f2ff;outline:0}.landing-news-select-button svg{width:16px;height:16px;transition:transform .2s}.landing-news-select-menu{position:absolute;left:0;right:0;top:calc(100% + 6px);z-index:160;overflow:hidden;border:1px solid #cddff5;border-radius:12px;background:#fff;padding:4px;box-shadow:0 12px 25px rgb(15 23 42 / .16)}.landing-news-select-menu button{display:block;width:100%;border-radius:8px;padding:10px 12px;color:#334155;font-size:13px;font-weight:700;text-align:left}.landing-news-select-menu button:hover,.landing-news-select-menu button.is-selected{background:#e8f2ff;color:#07265d}
</style>
<div class="landing-news-admin mx-auto max-w-[1280px] space-y-4" x-data="{ createOpen:false, editOpen:null }">
    <section class="landing-news-hero">
        <div><p>Konten landing page</p><h2>Berita & informasi sekolah</h2><span>Kelola berita yang tampil pada halaman utama dan halaman informasi publik.</span></div>
        <button type="button" @click="createOpen=true">+ Buat berita</button>
    </section>

    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

    <section class="landing-news-list">
        <div class="landing-news-list-head"><div><h3>Daftar berita</h3><p>{{ $articles->total() }} berita tersimpan</p></div><span>Landing page</span></div>
        <div class="divide-y divide-slate-100">
            @forelse($articles as $article)
                <article class="landing-news-row">
                    <div class="landing-news-image">@if(!empty($article->gallery[0]))<img src="{{ asset($article->gallery[0]) }}" alt="">@else<span>Berita</span>@endif</div>
                    <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="landing-news-status {{ $article->status === 'publish' ? 'is-published' : '' }}">{{ $article->status === 'publish' ? 'Terbit' : 'Draf' }}</span><time>{{ ($article->tampil_mulai ?: $article->created_at)?->format('d M Y') }}</time></div><h3>{{ $article->judul }}</h3><p>{{ str($article->isi)->limit(145) }}</p></div>
                    <div class="landing-news-actions"><button type="button" @click="editOpen={{ $article->id }}">Ubah</button><form method="POST" action="{{ route('admin.berita-landing.destroy', $article) }}" onsubmit="return confirm('Hapus berita ini?')">@csrf @method('DELETE')<button>Hapus</button></form></div>
                </article>

                <div x-cloak x-show="editOpen === {{ $article->id }}" class="landing-news-editor">
                    @include('admin.berita-landing.form', ['article' => $article, 'action' => route('admin.berita-landing.update', $article), 'method' => 'PUT', 'submit' => 'Simpan perubahan'])
                </div>
            @empty
                <p class="p-10 text-center text-sm font-semibold text-slate-400">Belum ada berita. Buat informasi pertama untuk landing page.</p>
            @endforelse
        </div>
        <x-per-page-pagination :paginator="$articles" />
    </section>

    <div x-cloak x-show="createOpen" class="fixed inset-0 z-[120] overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" @click.self="createOpen=false"><section class="mx-auto my-8 w-full max-w-2xl rounded-3xl bg-white shadow-2xl"><div class="flex items-center justify-between border-b border-slate-100 px-6 py-4"><div><h2 class="font-black text-slate-950">Buat berita</h2><p class="text-xs font-semibold text-slate-500">Berita akan muncul di landing page saat statusnya diterbitkan.</p></div><button @click="createOpen=false" class="text-sm font-bold text-slate-500">Tutup</button></div><div class="p-6">@include('admin.berita-landing.form', ['article' => null, 'action' => route('admin.berita-landing.store'), 'method' => 'POST', 'submit' => 'Simpan berita'])</div></section></div>
</div>
@endsection
