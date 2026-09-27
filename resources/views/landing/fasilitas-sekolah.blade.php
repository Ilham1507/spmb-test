@extends('layouts.school')
@section('title', 'Fasilitas Sekolah')
@section('content')
@php
    $facilities = [
        ['number' => '01', 'name' => 'Kelas Akademik', 'category' => 'akademik', 'description' => 'Ruang kelas dilengkapi dengan papan tulis, proyektor, dan peralatan presentasi untuk mendukung pembelajaran.', 'image' => 'cards/kelas.png'],
        ['number' => '02', 'name' => 'Perpustakaan', 'category' => 'akademik', 'description' => 'Koleksi buku pelajaran dan berbagai referensi lainnya tersedia untuk mendukung pembelajaran siswa.', 'image' => 'cards/perpustakaan.png'],
        ['number' => '03', 'name' => 'Laboratorium Farmasi', 'category' => 'praktik', 'description' => 'Dilengkapi dengan peralatan dan bahan untuk eksperimen kimia serta praktikum kefarmasian.', 'image' => 'cards/farmasi.png'],
        ['number' => '04', 'name' => 'Laboratorium Komputer', 'category' => 'akademik', 'description' => 'Ruang komputer dengan perangkat keras dan perangkat lunak terbaru untuk mendukung pembelajaran komputer dan teknologi informasi.', 'image' => 'cards/komputer.png'],
        ['number' => '05', 'name' => 'Masjid dan Tempat Wudhu', 'category' => 'pendukung', 'description' => 'Ruang yang luas dan nyaman untuk aktivitas ibadah, kegiatan keagamaan, serta kegiatan positif lainnya, dengan tempat wudhu sebagai fasilitas pendukung.', 'image' => 'cards/masjid.png'],
        ['number' => '06', 'name' => 'Lapangan Olahraga', 'category' => 'pendukung', 'description' => 'Lapangan dengan fasilitas untuk berbagai olahraga, seperti sepak bola, voli, dan basket.', 'image' => 'cards/lapangan.png'],
        ['number' => '07', 'name' => 'Kitchen', 'category' => 'praktik', 'description' => 'Tempat praktik kuliner dan kegiatan produksi Teaching Factory (Tefa) Kuliner dengan peralatan yang memadai.', 'image' => 'campus-carousel-2.png'],
        ['number' => '08', 'name' => 'Workshop Busana', 'category' => 'praktik', 'description' => 'Ruang praktik busana dan Tefa Busana yang dilengkapi perlengkapan untuk mendesain, membuat pola, memotong, serta menjahit busana.', 'image' => 'cards/busana.png'],
        ['number' => '09', 'name' => 'Laboratorium Keperawatan', 'category' => 'praktik', 'description' => 'Ruang khusus untuk kegiatan praktik keperawatan, didukung oleh perlengkapan yang memadai.', 'image' => 'campus-carousel-3.png'],
        ['number' => '10', 'name' => 'Kantin Sekolah', 'category' => 'pendukung', 'description' => 'Tempat membeli makanan dan minuman, sekaligus ruang untuk berkumpul dan bersosialisasi.', 'image' => 'cards/kantin.png'],
        ['number' => '11', 'name' => 'Taman', 'category' => 'pendukung', 'description' => 'Area hijau untuk beristirahat, berekreasi, dan melakukan kegiatan luar ruangan.', 'image' => 'cards/taman.png'],
        ['number' => '12', 'name' => 'UKS', 'category' => 'pendukung', 'description' => 'Ruang UKS dilengkapi dengan peralatan medis ringan untuk mendukung perawatan kesehatan umum.', 'image' => 'cards/uks.png'],
        ['number' => '13', 'name' => 'Ruangan Pertemuan', 'category' => 'pendukung', 'description' => 'Ruang untuk rapat kelompok, konseling siswa, dan sesi pembinaan.', 'image' => 'cards/pertemuan.png'],
        ['number' => '14', 'name' => 'Asrama', 'category' => 'pendukung', 'description' => 'Tempat tinggal bagi siswa yang ingin menetap dan mengikuti kegiatan asrama.', 'image' => 'cards/asrama.png'],
        ['number' => '15', 'name' => 'Kamar Kecil', 'category' => 'pendukung', 'description' => 'Kamar kecil tersedia secara terpisah untuk pria dan wanita.', 'image' => 'cards/toilet.png'],
    ];
    $categories = ['akademik' => 'Pembelajaran', 'praktik' => 'Praktik Kejuruan', 'pendukung' => 'Kehidupan Sekolah'];
@endphp
<style>
.fac{--navy:#092e6a;--blue:#1768d1;--muted:#5d7493;--line:#d8e6f5;background:#f7fbff;color:var(--navy);overflow:clip}
.fac *{box-sizing:border-box}.fac-wrap{width:min(1160px,calc(100% - 40px));margin:auto}.fac h1,.fac h2,.fac h3,.fac p{margin:0}
.fac h1,.fac h2,.fac h3{font-family:'Plus Jakarta Sans',sans-serif;font-weight:800}.fac p{line-height:1.8;font-size:15px}
.fac-label{font-size:10px;font-weight:800;letter-spacing:.19em;text-transform:uppercase;color:var(--blue)}
.fac-hero{position:relative;padding:64px 0 56px;background:radial-gradient(ellipse at 100% 0,#dcefff,transparent 65%),#f0f7ff}
.fac-hero-grid{display:grid;grid-template-columns:1.03fr 1fr;gap:60px;align-items:center}.fac h1{font-size:clamp(40px,4.5vw,58px);line-height:1.12;letter-spacing:-.055em;margin:18px 0 24px}.fac h1 span{color:#2172c8}
.fac-hero-copy{color:var(--muted);max-width:480px}.fac-btn{display:inline-flex;align-items:center;gap:24px;border:0;border-radius:12px;background:var(--navy);color:#fff;padding:15px 20px;font-size:13px;font-weight:700;text-decoration:none;transition:transform .25s,box-shadow .25s}.fac-btn:hover{transform:translateY(-3px);box-shadow:0 10px 24px #092e6a20}.fac-hero .fac-btn{margin-top:28px}
.fac-visual{position:relative;margin:0;padding:0 0 30px 24px}.fac-visual:before{content:'';position:absolute;inset:28px 24px 0 0;border:1px solid #a9c7e6;border-radius:24px}
.fac-visual img{position:relative;width:100%;height:350px;display:block;object-fit:cover;border-radius:24px 80px 24px 24px}
.fac-visual figcaption{position:absolute;bottom:42px;right:14px;padding:6px 10px;border-radius:7px;background:#ffffffeb;color:#466184;font-size:10px}
.fac-badge{position:absolute;left:0;bottom:0;background:#092e6a;color:#fff;padding:18px 24px;border-radius:16px;box-shadow:0 14px 28px #092e6a20;display:flex;align-items:center;gap:18px;animation:fac-float 6s ease-in-out 2}
.fac-badge strong{font:800 46px/1 'Plus Jakarta Sans',sans-serif;color:#ffd248;letter-spacing:-.06em}.fac-badge span{font-size:12px;line-height:1.6;max-width:110px}
.fac-principles{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:54px;padding-top:26px;border-top:1px solid #ccdeef}.fac-principles div{display:flex;align-items:center;gap:14px;font-size:13px;font-weight:700}.fac-principles b{display:grid;place-items:center;width:32px;height:32px;border:1px solid #b8d3ed;border-radius:50%;color:var(--blue);font-size:11px}
.fac-catalog{padding:76px 0 80px;scroll-margin-top:115px}.fac-head{display:flex;align-items:end;justify-content:space-between;gap:35px;margin-bottom:30px}.fac h2{font-size:clamp(30px,3.1vw,42px);line-height:1.18;letter-spacing:-.045em;margin-top:12px}.fac-head p{max-width:340px;color:var(--muted);font-size:14px}
.fac-toolbar{display:flex;align-items:center;justify-content:space-between;gap:20px;padding-bottom:26px}.fac-filters{display:flex;flex-wrap:wrap;gap:8px}.fac-filter{border:1px solid var(--line);background:#fff;color:#496887;padding:11px 15px;border-radius:10px;font:700 12px 'DM Sans',sans-serif;cursor:pointer;transition:background .2s,color .2s,transform .2s}.fac-filter:hover{transform:translateY(-2px);border-color:#91b9e3}.fac-filter[aria-pressed="true"]{background:var(--navy);color:#fff;border-color:var(--navy)}.fac-count{font-size:11px;color:var(--muted);white-space:nowrap}
.fac-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
.fac-filters[hidden]{display:none}
.fac-card{position:relative;padding:28px;background:#fff;border:1px solid var(--line);border-radius:22px;display:flex;flex-direction:column;min-height:286px;transition:transform .35s,box-shadow .35s,opacity .6s,border-color .35s}
.fac-card:hover{transform:translateY(-6px);border-color:#a4c7e8;box-shadow:0 18px 36px #123f7810}.fac-card[hidden]{display:none!important}
.fac-card[data-category="praktik"]{background:#edf5ff}.fac-card[data-category="pendukung"] .fac-icon{background:#edf6f3;color:#327f6a}
.fac-card-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:23px}
.fac-icon{display:grid;place-items:center;width:48px;height:48px;border:1px solid #d7e7f7;border-radius:14px;background:#eaf3ff;color:#1f6dbb}.fac-icon svg{width:25px;height:25px}
.fac-num{font:700 12px 'Plus Jakarta Sans',sans-serif;color:#7894b3;letter-spacing:.07em}.fac-type{font-size:9px;letter-spacing:.13em;text-transform:uppercase;color:#587fa7;font-weight:800}
.fac-card h3{font-size:21px;line-height:1.3;letter-spacing:-.03em;margin:8px 0 12px}.fac-card p{font-size:13px;color:var(--muted);line-height:1.8}
.fac-end{margin-top:46px;padding:32px 36px;border-radius:22px;background:#092e6a;display:flex;align-items:center;justify-content:space-between;gap:30px;color:#fff}.fac-end h2{font-size:25px;margin:0}.fac-end p{font-size:13px;color:#c6def6;margin-top:10px;max-width:620px}.fac-end .fac-btn{background:#ffd248;color:#092e6a;flex-shrink:0}
.fac a:focus-visible,.fac button:focus-visible{outline:3px solid #eab52e;outline-offset:4px}
.fac-reveal{transition:opacity .65s,transform .65s cubic-bezier(.2,.8,.2,1)}.fac-pending{opacity:0;transform:translateY(22px)}
@keyframes fac-float{50%{transform:translateY(-8px)}}
@media(max-width:850px){.fac-hero-grid{grid-template-columns:1fr;gap:38px}.fac-hero{padding-top:42px}.fac-hero-copy{max-width:640px}.fac-visual{max-width:650px}.fac-visual img{height:340px}.fac-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.fac-head{align-items:start;flex-direction:column;gap:20px}.fac-head p{max-width:550px}.fac-toolbar{align-items:start;flex-direction:column;gap:14px}.fac-principles{gap:12px}.fac-principles div{font-size:12px;align-items:start}.fac-principles b{flex-shrink:0}.fac-end{align-items:start;flex-direction:column}}
@media(max-width:540px){.fac-wrap{width:calc(100% - 32px)}.fac h1{font-size:40px}.fac-visual{padding-left:16px}.fac-visual img{height:270px;border-top-right-radius:55px}.fac-badge{padding:14px 18px}.fac-badge strong{font-size:38px}.fac-visual figcaption{bottom:105px;font-size:9px}.fac-principles{grid-template-columns:1fr;gap:15px;margin-top:35px}.fac-principles div{align-items:center}.fac-catalog{padding:50px 0}.fac-grid{grid-template-columns:1fr}.fac-card{min-height:0;padding:25px}.fac-card-top{margin-bottom:20px}.fac-filter{padding:10px 12px;font-size:11px}.fac-end{padding:28px 24px}}
@media(prefers-reduced-motion:reduce){.fac *{animation:none!important;transition:none!important}.fac-pending{opacity:1;transform:none}}
@media print{.fac-toolbar,.fac-hero .fac-btn,.fac-end{display:none}.fac-card[hidden]{display:flex!important}.fac-pending{opacity:1;transform:none}.fac-card{break-inside:avoid}}
.fac-card-media{position:relative;margin:-28px -28px 24px;overflow:hidden;border-radius:21px 21px 0 0;background:#dce8f4}
.fac-card-media img{display:block;width:100%;aspect-ratio:3/2;height:auto;object-fit:cover;transition:transform .6s ease}
.fac-card:hover .fac-card-media img{transform:scale(1.045)}
.fac-card-media figcaption{position:absolute;right:12px;bottom:12px;padding:4px 8px;border-radius:6px;background:#ffffffea;color:#48617e;font-size:10px}
.fac-photo-number{position:absolute;top:15px;left:15px;padding:8px 11px;border-radius:9px;background:#fff;color:#092e6a;font-size:12px;font-weight:800;box-shadow:0 4px 12px #092e6a18}
@media(max-width:540px){.fac-card-media{margin:-25px -25px 22px}}
</style>
<div class="fac">
    <section class="fac-hero" aria-labelledby="fac-title">
        <div class="fac-wrap">
            <div class="fac-hero-grid">
                <div>
                    <span class="fac-label">Pendidikan · Fasilitas Sekolah</span>
                    <h1 id="fac-title">Ruang untuk belajar.<br><span>Tempat untuk tumbuh.</span></h1>
                    <p class="fac-hero-copy">Kenali fasilitas SMK Muhammadiyah 4 Cileungsi yang mendukung pembelajaran, praktik kejuruan, ibadah, dan keseharian siswa di sekolah.</p>
                    <a class="fac-btn" href="#daftar-fasilitas">Jelajahi fasilitas <span aria-hidden="true">↓</span></a>
                </div>
                <figure class="fac-visual">
                    <img src="{{ asset('images/landing/campus-carousel-3.png') }}" alt="Ilustrasi kegiatan pembelajaran di laboratorium kesehatan" width="1824" height="864" fetchpriority="high">
                    <figcaption>Ilustrasi pembelajaran kejuruan</figcaption>
                    <div class="fac-badge"><strong>15</strong><span>fasilitas pendukung kegiatan siswa</span></div>
                </figure>
            </div>
            <div class="fac-principles" aria-label="Fungsi fasilitas sekolah">
                <div><b>01</b><span>Mendukung pembelajaran</span></div>
                <div><b>02</b><span>Menguatkan keterampilan</span></div>
                <div><b>03</b><span>Menunjang keseharian</span></div>
            </div>
        </div>
    </section>
    <section class="fac-catalog fac-wrap" id="daftar-fasilitas" aria-labelledby="fac-catalog-title">
        <header class="fac-head fac-reveal"><div><span class="fac-label">Kenali lingkungan sekolah</span><h2 id="fac-catalog-title">Setiap ruang, punya peran.</h2></div><p>Dari ruang kelas hingga asrama, setiap fasilitas hadir untuk mendukung kegiatan dan kebutuhan siswa.</p></header>
        <div class="fac-toolbar">
            <div class="fac-filters" role="group" aria-label="Filter kategori fasilitas" hidden>
                <button type="button" class="fac-filter" data-filter="all" aria-pressed="true" aria-controls="fac-grid">Semua fasilitas</button>
                @foreach ($categories as $key => $label)
                    <button type="button" class="fac-filter" data-filter="{{ $key }}" aria-pressed="false" aria-controls="fac-grid">{{ $label }}</button>
                @endforeach
            </div>
            <p class="fac-count" role="status" aria-live="polite" aria-atomic="true">Menampilkan 15 fasilitas</p>
        </div>
        <div class="fac-grid" id="fac-grid">
            @foreach ($facilities as $facility)
                <article class="fac-card fac-reveal" data-category="{{ $facility['category'] }}">
                    <figure class="fac-card-media">
                        <img src="{{ asset('images/landing/' . $facility['image']) }}" alt="Ilustrasi {{ $facility['name'] }}" width="1536" height="1024" loading="lazy" decoding="async">
                        <span class="fac-photo-number">{{ $facility['number'] }}</span>
                        <figcaption>Ilustrasi</figcaption>
                    </figure>
                    <span class="fac-type">{{ $categories[$facility['category']] }}</span>
                    <h3>{{ $facility['name'] }}</h3>
                    <p>{{ $facility['description'] }}</p>
                </article>
            @endforeach
        </div>
        <aside class="fac-end fac-reveal"><div><h2>Kenali sekolah lebih dekat.</h2><p>Hubungi sekolah untuk informasi lebih lanjut mengenai fasilitas dan lingkungan belajar di SIMUPA.</p></div><a class="fac-btn" href="{{ route('kontak') }}">Hubungi sekolah <span aria-hidden="true">↗</span></a></aside>
    </section>
</div>
<script>
(() => {
    const root = document.querySelector('.fac');
    if (!root) return;
    const cards = [...root.querySelectorAll('.fac-card')];
    const filters = [...root.querySelectorAll('.fac-filter')];
    const status = root.querySelector('.fac-count');
    root.querySelector('.fac-filters').hidden = false;
    filters.forEach(button => button.addEventListener('click', () => {
        filters.forEach(filter => filter.setAttribute('aria-pressed', String(filter === button)));
        let visible = 0;
        cards.forEach(card => {
            card.hidden = button.dataset.filter !== 'all' && card.dataset.category !== button.dataset.filter;
            card.classList.remove('fac-pending');
            if (!card.hidden) visible++;
        });
        status.textContent = 'Menampilkan ' + visible + ' fasilitas' + (button.dataset.filter === 'all' ? '' : ' · ' + button.textContent.trim());
    }));
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (motion.matches || !('IntersectionObserver' in window)) return;
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.remove('fac-pending');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.06 });
    root.querySelectorAll('.fac-reveal').forEach(element => {
        if (element.getBoundingClientRect().top > window.innerHeight) {
            element.classList.add('fac-pending');
            observer.observe(element);
        }
    });
    motion.addEventListener('change', () => {
        if (motion.matches) {
            root.querySelectorAll('.fac-pending').forEach(element => element.classList.remove('fac-pending'));
            observer.disconnect();
        }
    });
})();
</script>
@endsection
