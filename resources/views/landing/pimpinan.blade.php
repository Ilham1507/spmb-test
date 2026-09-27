@extends('layouts.school')

@section('title', 'Struktur organisasi sekolah')

@section('content')
<style>
    .org-page { background: #f7fbff; color: #092e6a; padding: 38px 0 74px; }
    .org-wrap { width: min(1440px, calc(100% - 40px)); margin: auto; }
    .org-head { display: flex; align-items: end; justify-content: space-between; gap: 28px; margin-bottom: 27px; }
    .org-label { margin: 0; color: #1768d1; font-size: 10px; font-weight: 800; letter-spacing: .2em; }
    .org-head h1 { margin: 9px 0 0; font: 800 clamp(32px, 4vw, 49px)/1.12 'Plus Jakarta Sans', sans-serif; letter-spacing: -.05em; }
    .org-intro { max-width: 350px; margin: 0; color: #607c9f; font-size: 14px; line-height: 1.65; }
    .org-board { margin: 0; overflow: hidden; border: 1px solid #d5e8fa; border-radius: 24px; background: #fff; box-shadow: 0 16px 36px #0b4b8c0e; }
    .org-image-scroll { overflow-x: auto; }
    .org-image-scroll:focus-visible { outline: 3px solid #1768d1; outline-offset: -3px; }
    .org-image { display: block; width: 100%; min-width: 1100px; height: auto; }
    .org-caption { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 16px 24px; border-top: 1px solid #e4edf7; color: #607c9f; font-size: 12px; line-height: 1.6; }
    .org-caption a { flex-shrink: 0; color: #125bb3; font-weight: 700; text-underline-offset: 4px; }
    .org-mobile-hint { display: none; }
    @media (max-width: 1139px) { .org-mobile-hint { display: inline; } }
    @media (max-width: 760px) {
        .org-page { padding: 28px 0 48px; }
        .org-wrap { width: calc(100% - 28px); }
        .org-head { display: block; }
        .org-intro { margin-top: 14px; }
        .org-board { border-radius: 18px; }
        .org-caption { align-items: start; flex-direction: column; padding: 16px; gap: 8px; }
    }
    @media print {
        .school-header, .school-footer, .org-head, .org-caption { display: none; }
        .org-page { padding: 0; background: #fff; }
        .org-wrap { width: 100%; }
        .org-board { border: 0; box-shadow: none; }
        .org-image-scroll { overflow: visible; }
        .org-image { min-width: 0; }
    }
</style>
<section class="org-page" aria-labelledby="org-heading">
    <div class="org-wrap">
        <header class="org-head">
            <div>
                <p class="org-label">TENTANG SEKOLAH</p>
                <h1 id="org-heading">Struktur organisasi</h1>
            </div>
            <p class="org-intro">Struktur organisasi SMK Muhammadiyah 4 Cileungsi periode 2026–2027.<span class="org-mobile-hint"> Geser gambar ke samping untuk melihat seluruh unit.</span></p>
        </header>
        <figure class="org-board">
            <div class="org-image-scroll" tabindex="0" role="region" aria-label="Bagan struktur organisasi, dapat digulir horizontal pada layar kecil">
                <img class="org-image" src="{{ asset('images/landing/struktur-organisasi-2026-2027.svg') }}"
                    width="1680" height="1040"
                    alt="Struktur organisasi periode 2026–2027: PCM Cileungsi, Majelis Dikdasmen, Kepala Sekolah Nunung Nuryati dan Tata Usaha. Waka Kurikulum dan HUBIN membawahi Kaprog Keperawatan dan Farmasi, Kasie Hubin, serta Kaprog Pariwisata. Waka Kesiswaan, Al-Islam dan KMD membawahi Kasie Kesiswaan/BP, Kasie Al-Islam, serta Kasie Asrama. Kasie Branding dan Lab. Kom terhubung ke kedua waka; Guru berada di bagian bawah.">
            </div>
            <figcaption class="org-caption">
                <span>Periode 2026–2027<span class="org-mobile-hint"> · Geser ke samping untuk membaca seluruh bagan.</span></span>
                <a href="{{ asset('images/landing/struktur-organisasi-2026-2027.svg') }}" download="struktur-organisasi-2026-2027.svg">Unduh gambar struktur</a>
            </figcaption>
        </figure>
    </div>
</section>
<script>
    // Mulai dari pimpinan di tengah gambar pada layar kecil.
    // Pengguliran tetap memakai perilaku browser, tanpa zoom atau drag khusus.
    const orgImageScroll = document.querySelector('.org-image-scroll');
    orgImageScroll.scrollLeft = (orgImageScroll.scrollWidth - orgImageScroll.clientWidth) / 2;
</script>
@endsection
