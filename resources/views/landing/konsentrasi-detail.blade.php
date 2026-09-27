@extends('layouts.school')

@section('title', $jurusan->name)

@section('content')
@php
    $italicFashion = static fn (string $text): string => str_replace(['FASHION', 'fashion'], ['<em>FASHION</em>', '<em>fashion</em>'], e($text));
    $concentrationDetails = [
        'Kuliner' => [
            'image' => 'images/konsentrasi/kuliner/praktik-memasak.png',
            'intro' => 'Di Kuliner, siswa belajar dari dapur praktik: mengolah bahan, menyajikan hidangan, dan membangun produk makanan yang siap dijual.',
            'gallery' => [
                ['images/konsentrasi/kuliner/praktik-memasak.png', 'PRAKTIK MEMASAK', 'Mengolah hidangan di dapur praktik'],
                ['images/konsentrasi/kuliner/plating-hidangan.png', 'PLATING', 'Menata sajian dengan teliti'],
                ['images/konsentrasi/kuliner/presentasi-produk.png', 'PRESENTASI', 'Menampilkan hasil karya kuliner'],
                ['images/konsentrasi/kuliner/kreasi-plating.png', 'KREASI MENU', 'Mengembangkan plating hidangan'],
                ['images/konsentrasi/kuliner/sajian-kuliner.png', 'PRODUK KULINER', 'Menyajikan produk siap santap'],
            ],
            'cards' => [
                ['title' => 'Yang dipelajari', 'body' => 'Teknik pengolahan makanan dan minuman, penyajian, sanitasi, serta pengelolaan produk boga.'],
                ['title' => 'Praktik utama', 'body' => 'Memasak, membuat produk kuliner, pelayanan boga, dan kegiatan Teaching Factory kuliner.'],
                ['title' => 'Arah masa depan', 'body' => 'Bekerja di industri kuliner, membangun usaha makanan, atau melanjutkan studi tata boga.'],
            ],
        ],
        'Desain dan Produksi Busana' => [
            'image' => 'images/konsentrasi/busana/praktik-menjahit.png',
            'intro' => 'Di Busana, siswa mengubah ide menjadi karya fashion melalui desain, pola, pemilihan bahan, hingga proses menjahit.',
            'gallery' => [
                ['images/konsentrasi/busana/praktik-menjahit.png', 'PRAKTIK MENJAHIT', 'Mengerjakan busana dengan mesin jahit industri'],
                ['images/konsentrasi/busana/finishing-busana.png', 'FINISHING', 'Merapikan dan menyelesaikan hasil busana'],
                ['images/konsentrasi/busana/peragaan-busana-01.png', 'PERAGAAN BUSANA', 'Menampilkan karya dalam kegiatan sekolah'],
                ['images/konsentrasi/busana/peragaan-busana-02.png', 'KARYA TRADISI', 'Mengolah inspirasi busana Nusantara'],
                ['images/konsentrasi/busana/karya-busana.png', 'KARYA FASHION', 'Membawa karya siswa ke ruang apresiasi'],
            ],
            'cards' => [
                ['title' => 'Yang dipelajari', 'body' => 'Desain busana, pembuatan pola, pemilihan bahan, teknik jahit, dan penyelesaian produk fashion.'],
                ['title' => 'Praktik utama', 'body' => 'Membuat pola, memotong bahan, menjahit, serta memproduksi busana melalui praktik dan Teaching Factory.'],
                ['title' => 'Arah masa depan', 'body' => 'Bekerja di industri fashion, menjadi penjahit atau perancang, membuka usaha, atau melanjutkan studi mode.'],
            ],
        ],
        'Layanan Kefarmasian Klinis dan Komunitas' => [
            'image' => 'images/konsentrasi/farmasi/pelayanan-kefarmasian.png',
            'intro' => 'Di Farmasi, siswa mempelajari pelayanan obat yang teliti, aman, dan berorientasi pada kebutuhan kesehatan masyarakat.',
            'gallery' => [
                ['images/konsentrasi/farmasi/pelayanan-kefarmasian.png', 'PELAYANAN FARMASI', 'Melatih alur pelayanan dan administrasi kefarmasian'],
                ['images/konsentrasi/farmasi/pencatatan-praktik.png', 'PENCATATAN', 'Mengerjakan pencatatan praktik dengan teliti'],
                ['images/konsentrasi/farmasi/pembelajaran-laboratorium.png', 'PEMBELAJARAN LAB', 'Belajar bersama di laboratorium farmasi'],
                ['images/konsentrasi/farmasi/laboratorium-farmasi.png', 'LABORATORIUM', 'Mengenal peralatan dan ruang praktik farmasi'],
                ['images/konsentrasi/farmasi/praktik-sediaan.png', 'PRAKTIK SEDIAAN', 'Menyiapkan kegiatan praktik kefarmasian'],
                ['images/konsentrasi/farmasi/penimbangan-bahan.png', 'PENIMBANGAN', 'Melatih ketelitian dalam pengolahan bahan'],
            ],
            'cards' => [
                ['title' => 'Yang dipelajari', 'body' => 'Dasar obat dan sediaan farmasi, pengelolaan stok, pelayanan kefarmasian, serta komunikasi kesehatan.'],
                ['title' => 'Praktik utama', 'body' => 'Meracik sediaan sederhana, menata obat, mengelola administrasi farmasi, dan simulasi pelayanan pasien.'],
                ['title' => 'Arah masa depan', 'body' => 'Berkarier di layanan farmasi sesuai ketentuan, bidang kesehatan, atau melanjutkan pendidikan farmasi.'],
            ],
        ],
        'Layanan Penunjang Keperawatan dan Caregiving' => [
            'image' => 'images/konsentrasi/keperawatan/praktik-perawatan-dasar.png',
            'intro' => 'Di Keperawatan dan Caregiving, siswa belajar memberikan pendampingan yang terampil, penuh perhatian, dan menghargai pasien.',
            'gallery' => [
                ['images/konsentrasi/keperawatan/praktik-perawatan-dasar.png', 'PRAKTIK PERAWATAN', 'Melatih keterampilan perawatan dasar di ruang caregiving'],
                ['images/konsentrasi/keperawatan/pemeriksaan-pasien.png', 'PEMERIKSAAN PASIEN', 'Menerapkan pengukuran dan observasi dengan teliti'],
                ['images/konsentrasi/keperawatan/administrasi-perawatan.png', 'ADMINISTRASI', 'Melatih komunikasi dan pencatatan layanan pasien'],
                ['images/konsentrasi/keperawatan/observasi-pasien.png', 'OBSERVASI', 'Mendampingi pasien dalam simulasi perawatan'],
            ],
            'cards' => [
                ['title' => 'Yang dipelajari', 'body' => 'Kebutuhan dasar pasien, kebersihan dan keselamatan, komunikasi terapeutik, serta pendampingan lansia.'],
                ['title' => 'Praktik utama', 'body' => 'Simulasi perawatan dasar, pemeriksaan sederhana, pendampingan pasien, dan praktik layanan caregiving.'],
                ['title' => 'Arah masa depan', 'body' => 'Menjadi tenaga penunjang layanan kesehatan atau caregiver, serta melanjutkan studi keperawatan dan kesehatan.'],
            ],
        ],
    ];
    $detail = $concentrationDetails[$jurusan->name] ?? [
        'image' => 'images/landing/campus-hero-v1.png',
        'intro' => $jurusan->visitorSummary(),
        'cards' => [
            ['title' => 'Yang dipelajari', 'body' => 'Kompetensi teori dan praktik yang relevan dengan bidang keahlian.'],
            ['title' => 'Praktik utama', 'body' => 'Proyek pembelajaran dan pengalaman kerja yang membangun keterampilan siswa.'],
            ['title' => 'Arah masa depan', 'body' => 'Persiapan bekerja, berwirausaha, atau melanjutkan pendidikan.'],
        ],
    ];
    $learningGallery = $detail['gallery'] ?? [
        [$detail['image'], 'PRAKTIK', 'Belajar di ruang praktik'],
        ['images/landing/activity-pramuka.png', 'KARAKTER', 'Tumbuh dalam kerja sama'],
        ['images/landing/activity-seni.png', 'KREATIVITAS', 'Mengembangkan karya'],
        ['images/landing/activity-futsal.png', 'KOLABORASI', 'Membangun kepercayaan diri'],
        ['images/landing/activity-btq.png', 'AKHLAK', 'Menguatkan nilai Islami'],
        ['images/landing/campus-carousel-3.png', 'KETERAMPILAN', 'Berlatih dengan terarah'],
        ['images/landing/campus-carousel-2.png', 'PRODUKSI', 'Menciptakan hasil karya'],
        ['images/landing/campus-hero-v1.png', 'MASA DEPAN', 'Menyiapkan langkah berikutnya'],
    ];
    $feeCategories = collect($rincianBiaya?->rincian_biaya ?? [])
        ->filter(fn ($item) => filled($item['name'] ?? null) && (float) ($item['amount'] ?? 0) > 0)
        ->groupBy(function ($item) {
            $name = strtolower((string) ($item['name'] ?? ''));

            return match (true) {
                str_contains($name, 'formulir'), str_contains($name, 'daftar'), str_contains($name, 'administrasi'), str_contains($name, 'tes') => 'Administrasi Pendaftaran',
                str_contains($name, 'seragam'), str_contains($name, 'atribut'), str_contains($name, 'jas'), str_contains($name, 'perlengkapan') => 'Seragam & Perlengkapan',
                default => 'Program Pembelajaran',
            };
        })
        ->map(fn ($items, $category) => ['name' => $category, 'amount' => $items->sum(fn ($item) => (float) ($item['amount'] ?? 0))])
        ->values();
@endphp
<style>
    .detail-page{background:#f5faff;color:#092e6a}.detail-wrap{width:min(1160px,calc(100% - 40px));margin:auto}.detail-hero{padding:60px 0 70px;background:linear-gradient(125deg,#082e68,#0d65b6);color:#fff}.detail-grid{display:grid;grid-template-columns:1fr .95fr;align-items:center;gap:52px}.detail-kicker{margin:0;color:#ffd248;font:800 11px 'DM Sans';letter-spacing:.2em}.detail-hero h1{margin:16px 0;color:#fff;font:800 clamp(42px,5vw,68px)/1 'Plus Jakarta Sans';letter-spacing:-.055em}.detail-hero p{max-width:560px;color:#d8ebff;font-size:16px;line-height:1.75}.detail-back{display:inline-flex;align-items:center;gap:8px;margin-top:25px;padding:12px 16px;border:1px solid #ffffff50;border-radius:13px;color:#fff;font:800 12px 'Plus Jakarta Sans';text-decoration:none;transition:background .2s,transform .2s}.detail-back:hover{background:#ffffff18;transform:translateX(-3px)}.detail-image{overflow:hidden;border:8px solid #ffffff22;border-radius:28px}.detail-image img{display:block;width:100%;height:360px;object-fit:cover}.detail-section{padding:72px 0}.detail-title{margin:0;color:#092e6a;font:800 clamp(28px,3.2vw,43px)/1.1 'Plus Jakarta Sans'}.detail-copy{max-width:700px;color:#5c789e;line-height:1.75}.outcome-grid,.alumni-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:28px}.outcome{padding:25px;border:1px solid #d9e9fa;border-radius:22px;background:#fff}.outcome b{display:block;color:#1670d3;font:800 12px 'DM Sans';letter-spacing:.14em}.outcome h3{margin:15px 0 8px;font:800 19px 'Plus Jakarta Sans'}.outcome p{margin:0;color:#5c789e;font-size:14px;line-height:1.6}.fee-panel{display:grid;grid-template-columns:1fr .9fr;gap:24px;padding:34px;border-radius:28px;background:#092e6a;color:#fff}.fee-panel h2{margin:0;font:800 30px 'Plus Jakarta Sans'}.fee-total{align-self:center;text-align:right;color:#ffd248;font:800 31px 'Plus Jakarta Sans'}.fee-items{grid-column:1/-1;border-top:1px solid #ffffff24;padding-top:15px}.fee-items div{display:flex;justify-content:space-between;gap:20px;padding:9px 0;color:#d9eaff;font-size:14px}.alumni{overflow:hidden;border-radius:22px;background:#fff;box-shadow:0 12px 28px #092e6a13}.alumni img{width:100%;height:185px;object-fit:cover}.alumni div{padding:18px}.alumni small{color:#1670d3;font:800 10px 'DM Sans';letter-spacing:.14em}.alumni h3{margin:7px 0 0;font:800 17px 'Plus Jakarta Sans'}@media(max-width:800px){.detail-wrap{width:calc(100% - 36px)}.detail-hero{padding:45px 0 55px}.detail-grid,.fee-panel{grid-template-columns:1fr}.detail-image img{height:270px}.outcome-grid,.alumni-grid{grid-template-columns:1fr}.fee-total{text-align:left}.detail-section{padding:55px 0}}
</style>
<style>
    .fee-card{overflow:hidden;border:1px solid #d6e8fb;border-radius:28px;background:#fff;box-shadow:0 16px 36px #092e6a12}.fee-head{display:flex;align-items:end;justify-content:space-between;gap:24px;padding:30px 34px;background:linear-gradient(120deg,#082e68,#0d5daa);color:#fff}.fee-head p{margin:0;color:#cfe5ff;font-size:14px}.fee-head h2{margin:8px 0 0;font:800 clamp(25px,3vw,36px)/1.1 'Plus Jakarta Sans'}.fee-total-box{flex:none;padding:15px 19px;border-radius:17px;background:#ffffff15;text-align:right}.fee-total-box small{display:block;color:#b9dafa;font:800 10px 'DM Sans';letter-spacing:.13em}.fee-total-box strong{display:block;margin-top:6px;color:#ffd248;font:800 25px 'Plus Jakarta Sans'}.fee-list{padding:9px 34px 20px}.fee-row{display:grid;grid-template-columns:44px 1fr auto;align-items:center;gap:14px;padding:16px 0;border-bottom:1px solid #e3eef9}.fee-row:last-child{border:0}.fee-index{display:grid;place-items:center;width:34px;height:34px;border-radius:11px;background:#e8f4ff;color:#146bd0;font:800 11px 'Plus Jakarta Sans'}.fee-name{color:#193d70;font-weight:700}.fee-amount{color:#092e6a;font:800 15px 'Plus Jakarta Sans'}.fee-empty{padding:28px 34px;color:#607b9f}.alumni-intro{display:flex;align-items:end;justify-content:space-between;gap:24px}.alumni-intro p{max-width:420px;margin:0;color:#607b9f;line-height:1.65}.alumni-gallery{display:grid;grid-template-columns:repeat(4,1fr);grid-auto-rows:180px;gap:14px;margin-top:28px}.alumni-tile{position:relative;overflow:hidden;border-radius:20px;background:#0b3978}.alumni-tile:nth-child(1),.alumni-tile:nth-child(6){grid-column:span 2;grid-row:span 2}.alumni-tile img{width:100%;height:100%;object-fit:cover;transition:transform .5s}.alumni-tile:hover img{transform:scale(1.07)}.alumni-tile:after{position:absolute;inset:0;background:linear-gradient(0deg,#061e47c9,transparent 58%);content:''}.alumni-caption{position:absolute;z-index:1;right:16px;bottom:14px;left:16px;color:#fff}.alumni-caption small{color:#ffd248;font:800 10px 'DM Sans';letter-spacing:.14em}.alumni-caption strong{display:block;margin-top:4px;font:800 15px 'Plus Jakarta Sans'}@media(max-width:800px){.fee-head{display:block;padding:25px}.fee-total-box{display:inline-block;margin-top:20px;text-align:left}.fee-list{padding:8px 22px 16px}.fee-row{grid-template-columns:36px 1fr}.fee-amount{grid-column:2}.alumni-intro{display:block}.alumni-intro p{margin-top:14px}.alumni-gallery{grid-template-columns:1fr 1fr;grid-auto-rows:145px;gap:10px}.alumni-tile:nth-child(1),.alumni-tile:nth-child(6){grid-column:span 2;grid-row:span 1}}
</style>
<style>
    .alumni-caption{right:13px;bottom:12px;left:13px}
    .alumni-caption small{font-size:8px;letter-spacing:.12em}
    .alumni-caption strong{margin-top:3px;font-size:13px;line-height:1.22}
    @media(max-width:800px){.alumni-caption{right:12px;bottom:11px;left:12px}.alumni-caption strong{font-size:12px;line-height:1.18}}
</style>
<style>
    .fee-category-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;padding:25px 34px 12px}
    .fee-category{display:grid;grid-template-columns:30px 1fr;gap:10px;align-items:center;min-height:94px;padding:16px;border:1px solid #dceafa;border-radius:17px;background:#f8fbff}
    .fee-category-index{display:grid;place-items:center;width:30px;height:30px;border-radius:10px;background:#e8f4ff;color:#146bd0;font:800 10px 'Plus Jakarta Sans'}
    .fee-category-name{color:#315787;font:700 12px/1.35 'Plus Jakarta Sans'}
    .fee-category strong{grid-column:2;color:#092e6a;font:800 15px 'Plus Jakarta Sans'}
    .fee-category-note{margin:0;padding:8px 34px 25px;color:#6684aa;font-size:12px;line-height:1.6}
    @media(max-width:800px){.fee-category-grid{grid-template-columns:1fr;padding:20px 22px 8px}.fee-category{min-height:0;padding:14px}.fee-category-note{padding:8px 22px 20px}}
</style>
<main class="detail-page">
    <section class="detail-hero"><div class="detail-wrap detail-grid"><div><p class="detail-kicker">KONSENTRASI KEAHLIAN</p><h1>{{ $jurusan->name }}</h1><p>{!! $italicFashion($jurusan->visitorSummary()) !!}</p><a class="detail-back" href="{{ route('jurusan') }}" aria-label="Kembali ke daftar konsentrasi keahlian">← Kembali ke konsentrasi keahlian</a></div><div class="detail-image"><img src="{{ asset($detail['image']) }}" alt="Pembelajaran {{ $jurusan->name }}"></div></div></section>
    <section class="detail-section"><div class="detail-wrap"><p class="detail-kicker" style="color:#1670d3">MENGENAL {{ strtoupper($jurusan->name) }}</p><h2 class="detail-title">Apa yang akan dipelajari?</h2><p class="detail-copy">{!! $italicFashion($detail['intro']) !!}</p><div class="outcome-grid">@foreach($detail['cards'] as $card)<article class="outcome"><b>0{{ $loop->iteration }}</b><h3>{{ $card['title'] }}</h3><p>{!! $italicFashion($card['body']) !!}</p></article>@endforeach</div></div></section>
    <section class="detail-section" style="padding-top:0"><div class="detail-wrap"><div class="alumni-intro"><div><p class="detail-kicker" style="color:#1670d3">PENGALAMAN BELAJAR</p><h2 class="detail-title">Belajar melalui praktik.</h2></div><p>Kegiatan pembelajaran dirancang untuk menguatkan keterampilan, ketelitian, kerja sama, dan kesiapan siswa pada bidang yang dipilih.</p></div><div class="alumni-gallery">@foreach ($learningGallery as [$image,$label,$caption])<article class="alumni-tile"><img src="{{ asset($image) }}" alt="{{ $caption }}"><div class="alumni-caption"><small>{{ $label }}</small><strong>{{ $caption }}</strong></div></article>@endforeach</div></div></section>
    <section class="detail-section" style="padding-top:0"><div class="detail-wrap"><div class="fee-card"><div class="fee-head"><div><p>DATA BIAYA SPMB</p><h2>Kategori biaya konsentrasi</h2><p>Disusun otomatis dari komponen biaya yang dikelola admin.</p></div><div class="fee-total-box"><small>TOTAL ESTIMASI</small><strong>{{ $rincianBiaya?->biaya_masuk ? 'Rp '.number_format($rincianBiaya->biaya_masuk,0,',','.') : 'Hubungi sekolah' }}</strong></div></div><div class="fee-category-grid">@forelse($feeCategories as $category)<article class="fee-category"><span class="fee-category-index">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><span class="fee-category-name">{{ $category['name'] }}</span><strong>Rp {{ number_format($category['amount'],0,',','.') }}</strong></article>@empty<div class="fee-empty">Kategori biaya akan muncul setelah admin menambahkan komponen biaya.</div>@endforelse</div><p class="fee-category-note">Nominal kategori dan total akan mengikuti perubahan data biaya oleh admin.</p></div></div></section>
</main>
@endsection
