@extends('layouts.school')
@section('title', 'Program Sekolah')
@section('content')
<style>
.ps{--ink:#092e6a;--muted:#56708e;--blue:#1768d1;--gold:#ffd248;background:#f7fbff;color:var(--ink);overflow:clip}
.ps *{box-sizing:border-box}.ps-wrap{width:min(1160px,calc(100% - 40px));margin:auto}
.ps h1,.ps h2,.ps h3,.ps p{margin:0}.ps h1,.ps h2,.ps h3{font-family:'Plus Jakarta Sans',sans-serif;font-weight:800}
.ps p{font-size:15px;line-height:1.85}.ps-tag{display:block;font-size:10px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;color:var(--blue)}
.ps-hero{position:relative;padding:72px 0 0;background:#071f45;color:#fff;isolation:isolate}
.ps-hero:before{content:'';position:absolute;inset:0;z-index:-1;background:radial-gradient(ellipse at 80% 15%,#196bc866,transparent 60%)}
.ps-hero-grid{display:grid;grid-template-columns:1.05fr 1fr;gap:60px;align-items:center;padding-bottom:64px}
.ps-hero .ps-tag{color:var(--gold)}.ps h1{font-size:clamp(44px,4.8vw,60px);line-height:1.08;letter-spacing:-.055em;margin:18px 0 24px}
.ps h1 em{font-style:normal;color:#ffd969}.ps-hero-copy{color:#c5d8ee;max-width:490px}
.ps-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:30px}.ps-button{display:inline-flex;align-items:center;gap:18px;padding:14px 20px;border-radius:12px;background:var(--gold);color:#092e6a;text-decoration:none;font-size:13px;font-weight:800;transition:transform .25s,box-shadow .25s}.ps-button:hover{transform:translateY(-3px);box-shadow:0 8px 24px #0002}.ps-button.alt{background:#ffffff0c;border:1px solid #ffffff45;color:#fff}
.ps-visual{position:relative;padding:0 0 22px 22px}.ps-photo{width:100%;height:430px;object-fit:cover;border-radius:100px 24px 24px 24px;display:block}.ps-visual:before{content:'';position:absolute;left:0;bottom:0;width:65%;height:65%;border:1px solid #ffffff40;border-radius:24px;z-index:-1}
.ps-photo-note{position:absolute;bottom:0;right:20px;padding:18px 23px;background:#fff;color:#092e6a;border-radius:16px;box-shadow:0 12px 40px #0003;animation:ps-float 6s ease-in-out 2}.ps-photo-note span{display:block;font-size:10px;letter-spacing:.15em;color:#607d9f;margin-bottom:5px}.ps-photo-note strong{font:800 17px 'Plus Jakarta Sans',sans-serif}
.ps-jump{display:flex;gap:0;border-top:1px solid #ffffff25}.ps-jump a{flex:1;padding:22px 12px;text-decoration:none;color:#d2e2f6;font-size:12px;transition:background .25s,color .25s}.ps-jump a:hover{color:#fff;background:#ffffff0d}.ps-jump b{color:var(--gold);margin-right:14px}
.ps-section{padding:88px 0;scroll-margin-top:120px}.ps-heading{display:grid;grid-template-columns:1fr 1fr;gap:60px;margin-bottom:36px;align-items:end}.ps h2{font-size:clamp(30px,3.3vw,44px);line-height:1.17;letter-spacing:-.045em;margin-top:13px}.ps-heading p,.ps-copy{color:var(--muted)}
.ps-intro{max-width:960px;color:var(--muted);margin-bottom:32px!important}.ps-grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.ps-card{padding:28px;border:1px solid #dbe7f5;border-radius:22px;background:#fff;transition:transform .3s,box-shadow .3s}.ps-card:hover{transform:translateY(-5px);box-shadow:0 18px 40px #092e6a0c}
.ps-number{display:inline-grid;place-items:center;width:42px;height:42px;border-radius:12px;background:#eaf3ff;font:800 13px 'Plus Jakarta Sans',sans-serif;color:var(--blue)}
.ps-card h3{font-size:21px;letter-spacing:-.03em;margin:25px 0 12px}.ps-card p{font-size:14px;color:var(--muted)}
.ps-card:nth-child(2){background:#eaf4ff}.ps-card:nth-child(3){background:#fff9e8}
.ps-subjects{margin-top:30px;border:1px solid #dbe7f5;border-radius:20px;background:#fff;overflow:hidden}
.ps-subjects summary{padding:24px 28px;cursor:pointer;font:800 17px 'Plus Jakarta Sans',sans-serif;list-style:none;display:flex;align-items:center;gap:18px}
.ps-subjects summary::-webkit-details-marker{display:none}.ps-subjects summary:after{content:'+';margin-left:auto;font-size:24px;color:var(--blue)}.ps-subjects[open] summary:after{content:'−'}
.ps-subject-list{list-style:none;display:grid;grid-template-columns:repeat(3,1fr);gap:0;margin:0;padding:0 28px 24px}.ps-subject-list li{padding:13px 12px;border-top:1px solid #e6eef8;font-size:13px;line-height:1.6}.ps-subject-list li:before{content:'•';color:#2683cc;margin-right:10px}
.ps-learning{display:grid;grid-template-columns:.8fr 1.2fr;gap:60px;padding-top:52px;align-items:start}.ps-learning h3{font-size:27px;line-height:1.3;letter-spacing:-.04em;margin:15px 0}.ps-steps{list-style:none;padding:0;margin:0;counter-reset:step}.ps-steps li{position:relative;padding:0 0 28px 48px;border-left:1px solid #c9ddef;margin-left:15px;counter-increment:step}.ps-steps li:last-child{border-left-color:transparent;padding-bottom:0}.ps-steps li:before{content:'0' counter(step);position:absolute;left:-16px;top:0;width:32px;height:32px;border-radius:50%;display:grid;place-items:center;background:#e7f1ff;color:#1768d1;font-size:11px;font-weight:800}.ps-steps h4{font:800 17px 'Plus Jakarta Sans',sans-serif;margin:0 0 8px}.ps-steps p{color:var(--muted);font-size:14px}
.ps-feature{background:#eaf3fc}.ps-industry{display:grid;grid-template-columns:.75fr 1.25fr;border-radius:28px;overflow:hidden;background:#092e6a;color:#fff}
.ps-duration{position:relative;padding:48px;background:linear-gradient(145deg,#1557a4,#092e6a);display:flex;flex-direction:column;justify-content:center;border-right:1px solid #ffffff22}
.ps-duration small{font-size:11px;letter-spacing:.15em;color:#c4dcf9}.ps-duration strong{display:block;font:800 clamp(76px,9vw,120px)/1.1 'Plus Jakarta Sans',sans-serif;color:var(--gold);letter-spacing:-.08em;margin:18px 0 4px}.ps-duration span{font-size:18px;font-weight:700}.ps-duration p{margin-top:20px;color:#c4dcf9;font-size:13px}
.ps-industry-copy{padding:44px}.ps-industry-copy h3{font-size:30px;letter-spacing:-.04em;margin:12px 0 20px}.ps-industry-copy .ps-tag{color:var(--gold)}.ps-industry-copy p{color:#d1e2f7;font-size:14px}.ps-industry-copy p+p{margin-top:14px}
.ps-pills{display:flex;flex-wrap:wrap;gap:8px;margin-top:24px}.ps-pills span{padding:8px 12px;border:1px solid #ffffff35;border-radius:8px;font-size:11px;color:#e8f2ff}
.ps-language{margin-top:44px;display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center}.ps-language h3{font-size:28px;letter-spacing:-.04em;margin-bottom:15px}.ps-language p{color:var(--muted);font-size:14px}
.ps-language-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.ps-language-card{display:flex;align-items:center;gap:18px;background:#fff;border:1px solid #d3e3f3;padding:24px;border-radius:16px;transition:transform .3s}.ps-language-card:hover{transform:translateY(-4px)}.ps-language-card b{font:800 26px 'Plus Jakarta Sans',sans-serif;color:#83a9d1}.ps-language-card span{font-size:14px;font-weight:700}
.ps-extra{display:grid;grid-template-columns:1fr 1fr;gap:22px}.ps-extra-card{padding:32px;border-radius:24px;background:#fff;border:1px solid #dce8f6}.ps-extra-card h3{font-size:25px;margin:18px 0 14px;letter-spacing:-.035em}.ps-extra-card p{color:var(--muted);font-size:14px}.ps-extra-card.wajib{background:#0c3a76;color:#fff}.ps-extra-card.wajib p{color:#d0e3fa}.ps-extra-card.wajib .ps-tag{color:#ffdb6e}
.ps-clubs{display:flex;flex-wrap:wrap;gap:9px;list-style:none;padding:0;margin:22px 0 0}.ps-clubs li{border:1px solid #d2e4f5;border-radius:9px;padding:10px 14px;font-size:13px;background:#f6faff;color:#15477f}.wajib .ps-clubs li{background:#ffffff0d;border-color:#ffffff40;color:#fff}
.ps-social{margin-top:28px;padding:36px;background:#e8f5f3;border:1px solid #cce6e0;border-radius:24px;display:grid;grid-template-columns:.9fr 1.1fr;gap:45px}.ps-social h3{font-size:27px;letter-spacing:-.035em;margin:14px 0}.ps-social .ps-tag{color:#287765}.ps-social p{color:#50736e;font-size:14px}.ps-social ul{list-style:none;padding:0;margin:0}.ps-social li{padding:14px 0;border-bottom:1px solid #bedbd4;font-size:14px;display:flex;gap:14px}.ps-social li:last-child{border:0}.ps-social li:before{content:'↗';color:#29816c}
.ps-close{margin-top:55px;padding-top:30px;border-top:1px solid #d7e5f3;display:flex;align-items:center;justify-content:space-between;gap:24px}.ps-close h3{font-size:24px;letter-spacing:-.04em}.ps-close p{color:var(--muted);font-size:14px;margin-top:8px}
.ps a:focus-visible,.ps summary:focus-visible{outline:3px solid #e4a91a;outline-offset:5px}
.ps-reveal{transition:opacity .7s ease,transform .7s cubic-bezier(.2,.8,.2,1)}.ps-reveal.ps-pending{opacity:0;transform:translateY(24px)}
@keyframes ps-float{50%{transform:translateY(-9px)}}
@media(max-width:800px){.ps-hero{padding-top:44px}.ps-hero-grid{grid-template-columns:1fr;gap:36px;padding-bottom:40px}.ps-photo{height:330px;border-top-left-radius:65px}.ps-visual{max-width:580px}.ps h1{font-size:48px}.ps-jump{flex-wrap:wrap}.ps-jump a{flex:1 1 50%;padding:17px 8px}.ps-section{padding:58px 0}.ps-heading,.ps-learning,.ps-language,.ps-social{grid-template-columns:1fr;gap:25px}.ps-grid3{grid-template-columns:1fr}.ps-card h3{margin-top:18px}.ps-subject-list{grid-template-columns:1fr 1fr}.ps-industry{grid-template-columns:1fr}.ps-duration{padding:30px;border-right:0;border-bottom:1px solid #ffffff22}.ps-duration strong{font-size:82px}.ps-industry-copy{padding:30px}.ps-extra{grid-template-columns:1fr}.ps-close{align-items:start;flex-direction:column}}
@media(max-width:440px){.ps-wrap{width:calc(100% - 32px)}.ps h1{font-size:41px}.ps-photo{height:285px}.ps-photo-note{right:8px;padding:14px}.ps-photo-note strong{font-size:14px}.ps-subject-list{grid-template-columns:1fr}.ps-subjects summary{font-size:15px;padding:20px}.ps-language-card{padding:18px 14px;gap:12px}.ps-language-card b{font-size:22px}.ps-extra-card,.ps-social{padding:25px}.ps-jump b{margin-right:7px}}
@media(prefers-reduced-motion:reduce){.ps *{animation:none!important;transition:none!important;scroll-behavior:auto!important}.ps-reveal.ps-pending{opacity:1;transform:none}}
.ps-program-image{position:relative;margin:0 0 24px;overflow:hidden;border-radius:16px;background:#dbe7f3}
.ps-program-image img{display:block;width:100%;aspect-ratio:3/2;height:auto;object-fit:cover;transition:transform .6s ease}
.ps-program-image:hover img{transform:scale(1.045)}
.ps-program-image figcaption{position:absolute;right:12px;bottom:12px;padding:4px 8px;border-radius:6px;background:#ffffffea;color:#48617e;font-size:10px}
.ps-image-card{margin:-28px -28px 24px;border-radius:21px 21px 0 0}
.ps-image-extra{margin:-32px -32px 28px;border-radius:23px 23px 0 0}
.ps-duration{padding:28px;justify-content:start}.ps-duration .ps-program-image{margin:-28px -28px 28px;border-radius:0}
.ps-language{align-items:start}.ps-language-grid{align-self:center}
.ps-extra{grid-template-columns:repeat(3,minmax(0,1fr))}
@media(max-width:1000px){.ps-extra{grid-template-columns:1fr}}
@media(max-width:440px){.ps-image-extra{margin:-25px -25px 24px}}
</style>
<div class="ps">
<section class="ps-hero" aria-labelledby="ps-title">
    <div class="ps-wrap ps-hero-grid">
        <div>
            <span class="ps-tag">Pendidikan · SMK Muhammadiyah 4 Cileungsi</span>
            <h1 id="ps-title">Program sekolah.<br><em>Ruang tumbuh</em><br>untuk masa depan.</h1>
            <p class="ps-hero-copy">Dari pembelajaran di kelas hingga pengalaman di industri. Dari penguatan iman hingga aksi untuk masyarakat. Temukan perjalanan belajar yang membentuk karakter dan kompetensi.</p>
            <div class="ps-actions"><a class="ps-button" href="#akademik">Jelajahi program <span aria-hidden="true">↓</span></a><a class="ps-button alt" href="#non-akademik">Kehidupan siswa <span aria-hidden="true">↗</span></a></div>
        </div>
        <div class="ps-visual">
            <img class="ps-photo" src="{{ asset('images/landing/campus-carousel-2.png') }}" alt="Ilustrasi pembelajaran praktik kuliner" width="1824" height="864" fetchpriority="high">
            <div class="ps-photo-note"><span>SEMANGAT PEMBELAJARAN</span><strong>Berkarakter. Terampil. Siap berkarya.</strong></div>
        </div>
    </div>
    <nav class="ps-wrap ps-jump" aria-label="Bagian program sekolah">
        <a href="#akademik"><b>01</b> Akademik</a><a href="#pembelajaran"><b>02</b> Pembelajaran</a><a href="#unggulan"><b>03</b> Program unggulan</a><a href="#non-akademik"><b>04</b> Non-akademik</a>
    </nav>
</section>

<section class="ps-section ps-wrap" id="akademik">
    <header class="ps-heading ps-reveal"><div><span class="ps-tag">01 / Program akademik</span><h2>Landasan yang kuat.<br>Kompetensi yang relevan.</h2></div><p>Kurikulum pemerintah, nilai-nilai Ismuba, dan kebutuhan industri berpadu untuk menumbuhkan generasi yang beriman, berakhlak, dan kompeten di bidangnya.</p></header>
    <p class="ps-intro ps-reveal">SMK Muhammadiyah 4 Cileungsi menerapkan kurikulum yang ditetapkan oleh pemerintah sebagai dasar penyelenggaraan pendidikan, sekaligus mengintegrasikan kurikulum Ismuba (Al Islam, Kemuhammadiyahan, dan Bahasa Arab) sebagai ciri khas dan ruh pembentukan karakter peserta didik. Kurikulum secara berkala disinkronkan dengan kebutuhan dan perkembangan dunia industri agar lulusan memiliki keterampilan yang relevan, adaptif, dan siap menghadapi tantangan dunia kerja yang terus berubah.</p>
    <div class="ps-grid3">
        <article class="ps-card ps-reveal"><figure class="ps-program-image ps-image-card"><img src="{{ asset('images/landing/cards/kelas.png') }}" alt="Ilustrasi ruang pembelajaran akademik" width="1536" height="1024" loading="lazy" decoding="async"><figcaption>Ilustrasi</figcaption></figure><span class="ps-number">01</span><h3>Kurikulum Pemerintah</h3><p>Menjadi dasar penyelenggaraan pendidikan dan pembelajaran di sekolah.</p></article>
        <article class="ps-card ps-reveal"><figure class="ps-program-image ps-image-card"><img src="{{ asset('images/landing/activity-btq.png') }}" alt="Ilustrasi pembelajaran Al Islam" width="1536" height="1024" loading="lazy" decoding="async"><figcaption>Ilustrasi</figcaption></figure><span class="ps-number">02</span><h3>Kurikulum Ismuba</h3><p>Al Islam, Kemuhammadiyahan, dan Bahasa Arab sebagai ciri khas pendidikan serta ruh pembentukan karakter peserta didik.</p></article>
        <article class="ps-card ps-reveal"><figure class="ps-program-image ps-image-card"><img src="{{ asset('images/landing/cards/busana.png') }}" alt="Ilustrasi ruang praktik kejuruan busana" width="1536" height="1024" loading="lazy" decoding="async"><figcaption>Ilustrasi</figcaption></figure><span class="ps-number">03</span><h3>Selaras dengan Industri</h3><p>Sinkronisasi kurikulum secara berkala mengikuti kebutuhan dan perkembangan dunia kerja.</p></article>
    </div>
    <details class="ps-subjects ps-reveal" open>
        <summary>Mata pelajaran utama yang diajarkan</summary>
        <ul class="ps-subject-list">
            @foreach (['Matematika', 'Bahasa Inggris', 'IPAS', 'Bahasa Indonesia', 'Seni dan Budaya', 'Teknologi Informasi dan Komunikasi (TIK)', 'Pendidikan Kewarganegaraan', 'Pendidikan Agama', 'Pendidikan Jasmani dan Kesehatan (PJOK)', 'Bahasa Jepang', 'Bahasa Arab', 'Kejuruan'] as $subject)
                <li>{{ $subject }}</li>
            @endforeach
        </ul>
    </details>
    <div class="ps-learning" id="pembelajaran" style="scroll-margin-top:130px">
        <div class="ps-reveal"><figure class="ps-program-image "><img src="{{ asset('images/landing/campus-carousel-3.png') }}" alt="Ilustrasi praktik keperawatan bersama pengajar" width="1536" height="1024" loading="lazy" decoding="async"><figcaption>Ilustrasi</figcaption></figure><span class="ps-tag">02 / Metode & pendekatan</span><h3>Siswa aktif.<br>Pengalaman nyata.</h3><p class="ps-copy">Metode pembelajaran dirancang bervariasi dengan prinsip utama berpusat pada siswa. Guru hadir sebagai fasilitator dalam setiap proses belajar.</p></div>
        <ol class="ps-steps">
            <li class="ps-reveal"><h4>Berpusat pada siswa</h4><p>Siswa didorong untuk aktif dan berpikir kritis, sekaligus mengembangkan keterampilan kolaboratif, kreatif, dan komunikatif.</p></li>
            <li class="ps-reveal"><h4>Lebih banyak praktik</h4><p>Porsi pembelajaran praktik lebih besar dibandingkan teori, terutama pada mata pelajaran kejuruan, agar siswa benar-benar terampil dalam bidangnya.</p></li>
            <li class="ps-reveal"><h4>PKL & magang di industri</h4><p>Praktik Kerja Lapangan (PKL) dan magang memperluas wawasan, menambah pengalaman nyata, serta memperkuat kompetensi sesuai kebutuhan dunia kerja.</p></li>
        </ol>
    </div>
</section>

<section class="ps-section ps-feature" id="unggulan">
    <div class="ps-wrap">
        <header class="ps-heading ps-reveal"><div><span class="ps-tag">03 / Program unggulan</span><h2>Lebih dekat ke industri.<br>Lebih terbuka pada dunia.</h2></div><p>Pengalaman kerja yang mendalam dan kemampuan bahasa internasional membuka ruang bagi siswa untuk berkembang lebih jauh.</p></header>
        <article class="ps-industry ps-reveal">
            <div class="ps-duration"><figure class="ps-program-image "><img src="{{ asset('images/landing/campus-carousel-2.png') }}" alt="Ilustrasi praktik kuliner di lingkungan kerja" width="1536" height="1024" loading="lazy" decoding="async"><figcaption>Ilustrasi</figcaption></figure><small>PENGALAMAN BELAJAR DI INDUSTRI</small><strong>±1,5</strong><span>tahun di dunia industri</span><p>Durasi Program Kelas Industri, lebih panjang dibandingkan PKL reguler.</p></div>
            <div class="ps-industry-copy"><span class="ps-tag">Belajar bersama dunia kerja</span><h3>Program Kelas Industri</h3><p>Program Kelas Industri memperkuat keterkaitan antara dunia pendidikan dan dunia kerja. Siswa menjalani pengalaman belajar di industri selama kurang lebih 1,5 tahun untuk memperoleh kompetensi yang lebih mendalam, membangun kebiasaan kerja profesional, dan mempersiapkan diri sesuai standar industri.</p><p>Pembelajaran di sekolah tetap berjalan dengan sistem blok. Saat siswa berada di industri, kegiatan belajar dapat dilaksanakan secara daring dengan penyesuaian terhadap jadwal kerja masing-masing.</p><div class="ps-pills"><span>Sistem blok</span><span>Pembelajaran daring</span><span>Standar industri</span></div></div>
        </article>
        <div class="ps-language">
            <div class="ps-reveal"><figure class="ps-program-image "><img src="{{ asset('images/landing/cards/bahasa.png') }}" alt="Ilustrasi kegiatan belajar bahasa internasional" width="1536" height="1024" loading="lazy" decoding="async"><figcaption>Ilustrasi</figcaption></figure><h3>Program Bahasa Internasional</h3><p>Bahasa Inggris, Jepang, Arab, dan Jerman membekali siswa dengan kemampuan komunikasi lintas budaya serta memperluas peluang di dunia global. Lulusan diharapkan mampu berkiprah di dalam maupun luar negeri, baik untuk memasuki dunia kerja maupun melanjutkan pendidikan ke jenjang yang lebih tinggi.</p></div>
            <div class="ps-language-grid">
                @foreach (['EN' => 'Bahasa Inggris', 'JP' => 'Bahasa Jepang', 'AR' => 'Bahasa Arab', 'DE' => 'Bahasa Jerman'] as $code => $language)
                    <div class="ps-language-card ps-reveal"><b aria-hidden="true">{{ $code }}</b><span>{{ $language }}</span></div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="ps-section ps-wrap" id="non-akademik">
    <header class="ps-heading ps-reveal"><div><span class="ps-tag">04 / Program non-akademik</span><h2>Temukan bakat.<br>Tumbuhkan kepedulian.</h2></div><p>Pengalaman di luar kelas menjadi ruang untuk mengembangkan minat, membangun karakter, dan memberikan manfaat bagi sesama.</p></header>
    <div class="ps-extra">
        <article class="ps-extra-card wajib ps-reveal">
            <figure class="ps-program-image ps-image-extra"><img src="{{ asset('images/landing/cards/tapak-suci.png') }}" alt="Ilustrasi buatan latihan Tapak Suci dengan seragam merah dan sabuk kuning" width="1536" height="1024" loading="lazy" decoding="async"><figcaption>Ilustrasi AI</figcaption></figure>
            <span class="ps-tag">Ekstrakurikuler / Wajib</span>
            <h3>Tapak Suci</h3>
            <p>Kegiatan pencak silat untuk melatih keterampilan bela diri, disiplin, dan pengendalian diri. Tapak Suci merupakan ekstrakurikuler wajib bagi seluruh siswa.</p>
        </article>
        <article class="ps-extra-card wajib ps-reveal">
            <figure class="ps-program-image ps-image-extra"><img src="{{ asset('images/landing/cards/hizbul-wathan.png') }}" alt="Ilustrasi buatan kegiatan kepanduan Hizbul Wathan dan kerja sama siswa" width="1536" height="1024" loading="lazy" decoding="async"><figcaption>Ilustrasi AI</figcaption></figure>
            <span class="ps-tag">Ekstrakurikuler / Wajib</span>
            <h3>Hizbul Wathan</h3>
            <p>Kegiatan kepanduan untuk mengembangkan kemandirian, kerja sama, dan tanggung jawab. Hizbul Wathan merupakan ekstrakurikuler wajib bagi seluruh siswa.</p>
        </article>
        <article class="ps-extra-card ps-reveal"><figure class="ps-program-image ps-image-extra"><img src="{{ asset('images/landing/activity-seni.png') }}" alt="Ilustrasi kegiatan seni dan pengembangan bakat" width="1536" height="1024" loading="lazy" decoding="async"><figcaption>Ilustrasi</figcaption></figure><span class="ps-tag">Ekstrakurikuler / Pilihan</span><h3>Ruang untuk minat & bakat.</h3><p>Siswa dapat memilih kegiatan ekstrakurikuler sesuai minat dan bakat yang ingin dikembangkan.</p><ul class="ps-clubs">@foreach (['Voli', 'Badminton', 'Panahan', 'IPM', 'Sarpala', 'Paskibra', 'Tari'] as $club)<li>{{ $club }}</li>@endforeach</ul></article>
    </div>
    <article class="ps-social ps-reveal">
        <div><figure class="ps-program-image "><img src="{{ asset('images/landing/cards/sosial.png') }}" alt="Ilustrasi kegiatan berbagi untuk masyarakat" width="1536" height="1024" loading="lazy" decoding="async"><figcaption>Ilustrasi</figcaption></figure><span class="ps-tag">Kegiatan sosial</span><h3>Belajar peduli.<br>Hadir untuk masyarakat.</h3><p>Komitmen sekolah terhadap pelayanan masyarakat diwujudkan melalui kegiatan amal dan layanan komunitas yang diadakan secara rutin, dengan keterlibatan langsung para siswa.</p></div>
        <ul><li>Membersihkan lingkungan</li><li>Membersihkan musala di sekitar sekolah</li><li>Layanan cek kesehatan gratis untuk masyarakat</li><li>Bakti sosial</li><li>Jumat Berbagi</li></ul>
    </article>
    <div class="ps-close ps-reveal"><div><h3>Mulai perjalanan belajarmu di SIMUPA.</h3><p>Kenali pilihan konsentrasi keahlian dan temukan bidang yang ingin kamu tekuni.</p></div><a class="ps-button" href="{{ route('jurusan') }}">Lihat konsentrasi keahlian <span aria-hidden="true">↗</span></a></div>
</section>
</div>
<script>
(() => {
    const root = document.querySelector('.ps');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (!root || reducedMotion.matches || !('IntersectionObserver' in window)) return;
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.remove('ps-pending');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.08 });
    root.querySelectorAll('.ps-reveal').forEach(element => {
        // Keep content above the fold and anchor destinations visible on first render.
        if (element.getBoundingClientRect().top > window.innerHeight) {
            element.classList.add('ps-pending');
            observer.observe(element);
        }
    });
    reducedMotion.addEventListener('change', () => {
        if (reducedMotion.matches) {
            root.querySelectorAll('.ps-pending').forEach(element => element.classList.remove('ps-pending'));
            observer.disconnect();
        }
    });
})();
</script>
@endsection
