@extends('layouts.school')

@section('title', $pengumuman->judul)

@section('content')
@php
    $images = collect($pengumuman->gallery ?? [])->filter()->values();
    if ($images->isEmpty()) {
        $images = collect(['images/landing/activity-seni.png']);
    }

    $paragraphs = collect(preg_split('/\R\s*\R/', (string) $pengumuman->isi))
        ->filter()
        ->values();

    $inlineMedia = collect($pengumuman->content_blocks ?? [])
        ->filter(fn ($media) => ($media['type'] ?? 'image') === 'image' && filled($media['path'] ?? null))
        ->map(fn ($media) => [
            'path' => $media['path'],
            'after_paragraph' => max(1, (int) ($media['after_paragraph'] ?? 1)),
            'caption' => $media['caption'] ?? null,
        ]);

    if ($inlineMedia->isEmpty()) {
        $inlineMedia = $images->slice(1)->values()->map(fn ($path, $index) => [
            'path' => $path,
            'after_paragraph' => $index + 1,
            'caption' => null,
        ]);
    }

    $inlineMedia = $inlineMedia->groupBy('after_paragraph');
@endphp

<style>
    .article-page{background:#fff;color:#092e6a}.article-page .article-wrap{width:min(1240px,calc(100% - 64px))!important;margin:auto}.article-top{padding:48px 0 30px;background:linear-gradient(125deg,#eff9ff,#dcedff)}.article-label{color:#1768d1;font:800 11px 'DM Sans';letter-spacing:.2em}.article-top h1{max-width:980px;margin:13px 0 18px;font:800 clamp(38px,3.4vw,60px)/1.1 'Plus Jakarta Sans'!important;letter-spacing:-.045em}.article-meta{margin:0;color:#5e7da5;font-size:16px;font-weight:700}.article-layout{display:grid;grid-template-columns:minmax(0,760px) minmax(220px,1fr);gap:68px;padding:44px 0 90px}.article-content{color:#3c608d;font-size:18px;line-height:1.9}.article-content p{margin:0 0 28px}.article-page .article-cover,.article-page .article-inline-image{width:100%!important;max-width:none!important;overflow:hidden;border-radius:22px;background:#eef6fd}.article-cover{aspect-ratio:16/10;margin:0 0 34px}.article-inline-gallery{display:grid;gap:14px;margin:36px 0}.article-inline-gallery.is-many{grid-template-columns:repeat(2,minmax(0,1fr))}.article-inline-image{margin:0;aspect-ratio:16/10}.article-cover img,.article-inline-image img{display:block;width:100%;height:100%!important;max-height:none;object-fit:cover}.article-inline-image figcaption{padding:10px 14px;background:#f7fbff;color:#5e7da5;font:600 13px/1.5 'DM Sans'}.article-side{align-self:start;margin-top:2px;padding:8px 0 8px 30px;border-left:2px solid #d7e9fb}.article-side h2{margin:0 0 18px;color:#092e6a;font:800 20px 'Plus Jakarta Sans'}.article-side a{display:block;padding:12px 0;border-bottom:1px solid #e1edf9;color:#54749b;font:700 14px 'DM Sans';text-decoration:none}.article-side a:hover,.article-side a.is-current{color:#1768d1}.article-back{display:inline-flex;margin-top:16px;padding:12px 16px;border:1px solid #1768d1;border-radius:12px;color:#1768d1;font:800 12px 'Plus Jakarta Sans';text-decoration:none}@media(max-width:800px){.article-page .article-wrap{width:calc(100% - 36px)!important}.article-top{padding:45px 0 26px}.article-top h1{font-size:39px!important}.article-layout{grid-template-columns:1fr;gap:0;padding:34px 0 60px}.article-content{font-size:16px}.article-side{display:none}.article-inline-gallery.is-many{grid-template-columns:1fr}}
</style>

<main class="article-page">
    <section class="article-top">
        <div class="article-wrap">
            <span class="article-label">INFORMASI SEKOLAH</span>
            <h1>{{ $pengumuman->judul }}</h1>
            <p class="article-meta">{{ ($pengumuman->tampil_mulai ?: $pengumuman->created_at ?: now())->translatedFormat('d F Y') }} • Humas SIMUPA</p>
        </div>
    </section>

    <div class="article-wrap">
        <div class="article-layout">
            <article class="article-content">
                <figure class="article-cover">
                    <img src="{{ asset($images->first()) }}" alt="{{ $pengumuman->judul }}">
                </figure>

                @forelse($paragraphs as $paragraph)
                    <p>{{ $paragraph }}</p>

                    @if($inlineMedia->has($loop->iteration))
                        @php($mediaAtPosition = $inlineMedia->get($loop->iteration))
                        <div class="article-inline-gallery {{ $mediaAtPosition->count() > 1 ? 'is-many' : '' }}">
                            @foreach($mediaAtPosition as $media)
                                <figure class="article-inline-image">
                                    <img src="{{ asset($media['path']) }}" alt="Dokumentasi {{ $pengumuman->judul }}">
                                    @if(filled($media['caption'] ?? null))
                                        <figcaption>{{ $media['caption'] }}</figcaption>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    @endif
                @empty
                    <p>Informasi selengkapnya akan segera diperbarui.</p>
                @endforelse

                <a class="article-back" href="{{ route('pengumuman') }}">Kembali ke informasi</a>
            </article>

            <aside class="article-side">
                <h2>Menu sekolah</h2>
                <a class="is-current" href="{{ route('pengumuman') }}">Informasi sekolah</a>
                <a href="{{ route('profil') }}">Profil sekolah</a>
                <a href="{{ route('jurusan') }}">Konsentrasi keahlian</a>
                <a href="{{ route('kontak') }}">Kontak sekolah</a>
                <a href="{{ route('kontak') }}">Daftar SPMB</a>
            </aside>
        </div>
    </div>
</main>
@endsection
