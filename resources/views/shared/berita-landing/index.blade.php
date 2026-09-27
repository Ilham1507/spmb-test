@extends($layout)

@section('title', 'Berita & Artikel')
@section('page_title', 'Berita & Artikel')

@section('content')
<style>
.landing-news-admin{--news-accent:var(--portal-accent,#0b3b83);--news-deep:var(--portal-accent-deep,#07265d);--news-soft:var(--portal-soft,#e8f2ff);--news-line:var(--portal-line,#cddff5)}
.landing-news-admin .landing-news-hero{border-color:var(--news-line);background:linear-gradient(120deg,var(--news-deep),var(--news-accent));box-shadow:0 17px 34px color-mix(in srgb,var(--news-accent) 18%,transparent)}
.landing-news-admin .landing-news-hero p{color:#ffdc74}.landing-news-admin .landing-news-hero button,.landing-news-admin .landing-news-hero a{color:var(--news-deep)}
.landing-news-admin .landing-news-list{border-color:var(--news-line);box-shadow:0 11px 25px color-mix(in srgb,var(--news-accent) 7%,transparent)}
.landing-news-admin .landing-news-list h3,.landing-news-admin .landing-news-row h3{color:var(--news-deep)}
.landing-news-admin .landing-news-list-head>span,.landing-news-admin .landing-news-image{background:var(--news-soft);color:var(--news-deep)}
.landing-news-admin .landing-news-status.is-published{background:var(--news-soft);color:var(--news-deep)}
.landing-news-admin .landing-news-save{background:linear-gradient(135deg,var(--news-accent),var(--news-deep))}
.landing-news-select{position:relative;margin-top:6px}.landing-news-select-button{display:flex;width:100%;align-items:center;justify-content:space-between;border:1px solid #cbd5e1;border-radius:10px;padding:10px 12px;background:#fff;color:#173d6b;font-size:13px;font-weight:700;text-align:left}.landing-news-select-button:focus{border-color:var(--news-accent);box-shadow:0 0 0 3px var(--news-soft);outline:0}.landing-news-select-button svg{width:16px;height:16px;transition:transform .2s}.landing-news-select-menu{position:absolute;left:0;right:0;top:calc(100% + 6px);z-index:160;overflow:hidden;border:1px solid var(--news-line);border-radius:12px;background:#fff;padding:4px;box-shadow:0 12px 25px rgb(15 23 42 / .16)}.landing-news-select-menu button{display:block;width:100%;border-radius:8px;padding:10px 12px;color:#334155;font-size:13px;font-weight:700;text-align:left}.landing-news-select-menu button:hover,.landing-news-select-menu button.is-selected{background:var(--news-soft);color:var(--news-deep)}
.landing-news-tools{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 20px;border-bottom:1px solid #e6eef8}.landing-news-search{display:flex;flex:1;gap:8px;min-width:0;max-width:720px}.landing-news-search input{min-width:0;flex:1;border:1px solid var(--news-line);border-radius:11px;padding:10px 12px;color:#173d6b;font-size:12px;font-weight:700;outline:0}.landing-news-search input:focus{border-color:var(--news-accent);box-shadow:0 0 0 3px var(--news-soft)}.landing-news-search button{flex:none;border-radius:11px;padding:10px 14px;background:var(--news-accent);color:#fff;font-size:12px;font-weight:800}.landing-news-tools .spmb-pagination{display:flex!important;flex:0 0 auto!important;flex-direction:row!important;align-items:center!important;justify-content:flex-end!important;gap:12px!important;border:0!important;background:transparent!important;padding:0!important}.landing-news-tools .spmb-pagination form{flex:none}.landing-news-tools .spmb-pagination-links nav{justify-content:flex-end}@media(max-width:760px){.landing-news-tools{align-items:stretch;flex-direction:column;padding:14px}.landing-news-search{max-width:none}.landing-news-tools .spmb-pagination{justify-content:space-between!important}.landing-news-tools .spmb-pagination-links nav{justify-content:flex-start}}.landing-news-actions>a{display:inline-flex;align-items:center;border:1px solid #d5e4f5;border-radius:9px;padding:7px 9px;background:#fff;color:#315b89;font-family:'DM Sans',sans-serif;font-size:10px;font-weight:800}.landing-news-admin .landing-news-form input:focus,.landing-news-admin .landing-news-form textarea:focus,.landing-news-admin .landing-news-form select:focus{border-color:var(--news-accent);box-shadow:0 0 0 3px var(--news-soft)}
</style>
<div class="landing-news-admin mx-auto max-w-[1280px] space-y-4">
    <section class="landing-news-hero">
        <div><p>Publikasi sekolah</p><h2>Berita & informasi sekolah</h2><span>Kelola berita dan artikel yang tampil pada halaman utama serta informasi publik.</span></div>
        <a href="{{ route($routePrefix.'.create') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-3 text-sm font-black shadow-sm">+ Buat berita</a>
    </section>

    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

    <section class="landing-news-list">
        <div class="landing-news-list-head"><div><h3>Daftar berita</h3><p>{{ $articles->total() }} berita tersimpan</p></div><span>Landing page</span></div>
        <div class="landing-news-tools">
            <form method="GET" class="landing-news-search">
                @if(request()->filled('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari judul atau isi berita" aria-label="Cari berita">
                <button>Cari</button>
            </form>
            <x-per-page-pagination :paginator="$articles" />
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($articles as $article)
                <article class="landing-news-row">
                    <div class="landing-news-image">@if(!empty($article->gallery[0]))<img src="{{ asset($article->gallery[0]) }}" alt="">@else<span>Berita</span>@endif</div>
                    <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="landing-news-status {{ $article->status === 'publish' ? 'is-published' : '' }}">{{ $article->status === 'publish' ? 'Terbit' : 'Draf' }}</span><time>{{ ($article->tampil_mulai ?: $article->created_at)?->format('d M Y') }}</time></div><h3>{{ $article->judul }}</h3><p>{{ str($article->isi)->limit(145) }}</p></div>
                    <div class="landing-news-actions"><a href="{{ route($routePrefix.'.edit', $article) }}">Ubah</a><form method="POST" action="{{ route($routePrefix.'.destroy', $article) }}" onsubmit="return confirm('Hapus berita ini?')">@csrf @method('DELETE')<button>Hapus</button></form></div>
                </article>
            @empty
                <p class="p-10 text-center text-sm font-semibold text-slate-400">Belum ada berita. Buat informasi pertama untuk landing page.</p>
            @endforelse
        </div>
    </section>

</div>
@endsection

