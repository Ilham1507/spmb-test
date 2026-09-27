<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses belum tersedia - SPMB Online</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--navy:#092e6a;--deep:#071b48;--blue:#1768d1;--gold:#ffd248;--ink:#102a53;--muted:#6881a5;--line:#d9e8f7}
        *{box-sizing:border-box}
        html,body{min-height:100%;margin:0}
        body{display:flex;align-items:flex-start;justify-content:center;overflow-x:hidden;padding:96px 28px 28px;background:#f4f8fc;color:var(--ink);font-family:'DM Sans',system-ui,sans-serif}
        body:before,body:after{position:fixed;z-index:-1;border-radius:999px;content:'';filter:blur(2px);pointer-events:none}
        body:before{top:-160px;right:-100px;width:440px;height:440px;background:radial-gradient(circle,#dceaff 0%,rgba(220,234,255,0) 70%)}
        body:after{bottom:-210px;left:-140px;width:520px;height:520px;background:radial-gradient(circle,#fff0bd 0%,rgba(255,240,189,0) 70%)}
        .error-shell{width:min(100%,900px);overflow:hidden;border:1px solid rgba(217,232,247,.95);border-radius:24px;background:#fff;box-shadow:0 24px 64px rgba(7,27,72,.12)}
        .error-top{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:11px 24px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.88)}
        .brand{display:flex;align-items:center;gap:12px;text-decoration:none;color:var(--ink)}
        .brand-mark{display:grid;width:44px;height:44px;place-items:center;border:1px solid #cfe1f6;border-radius:14px;background:#fff;box-shadow:0 8px 18px rgba(23,104,209,.1)}
        .brand-mark img{width:31px;height:31px;object-fit:contain}
        .brand strong{display:block;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:800;letter-spacing:.02em}
        .brand span{display:block;margin-top:3px;color:var(--muted);font-size:11px;font-weight:600}
        .status-pill{display:inline-flex;align-items:center;gap:7px;border:1px solid #f3df99;border-radius:999px;padding:8px 12px;background:#fff9e8;color:#8c6700;font-size:11px;font-weight:700}
        .status-pill i{width:7px;height:7px;border-radius:50%;background:var(--gold);box-shadow:0 0 0 4px rgba(255,210,72,.2)}
        .error-body{display:grid;grid-template-columns:minmax(210px,.72fr) minmax(0,1.28fr);align-items:center;gap:32px;padding:27px 54px 31px;background:linear-gradient(135deg,#fff 0%,#f7fbff 100%)}
        .illustration{position:relative;display:grid;min-height:205px;place-items:center}
        .illustration:before{position:absolute;width:215px;height:215px;border:1px solid #d9e8f7;border-radius:50%;content:''}
        .illustration:after{position:absolute;width:168px;height:168px;border-radius:50%;background:linear-gradient(145deg,#e8f2ff,#fff4d6);box-shadow:inset 0 0 0 1px rgba(255,255,255,.8);content:''}
        .code{position:relative;z-index:1;color:var(--navy);font-family:'Plus Jakarta Sans',sans-serif;font-size:72px;font-weight:800;letter-spacing:-.08em;line-height:1}
        .code small{display:block;margin-top:8px;color:var(--blue);font-family:'DM Sans',sans-serif;font-size:11px;font-weight:800;letter-spacing:.2em;text-align:center;text-transform:uppercase}
        .copy{max-width:530px}
        .kicker{margin:0 0 12px;color:var(--blue);font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase}
        h1{margin:0;color:var(--ink);font-family:'Plus Jakarta Sans',sans-serif;font-size:clamp(26px,3.6vw,40px);font-weight:800;letter-spacing:-.055em;line-height:1.08}
        .description{margin:11px 0 0;color:var(--muted);font-size:15px;line-height:1.55}
        .info{display:flex;align-items:flex-start;gap:10px;margin-top:16px;padding:11px 15px;border:1px solid #d9e8f7;border-radius:14px;background:#f7fbff;color:#52709b;font-size:12px;line-height:1.5}
        .info svg{flex:0 0 auto;margin-top:1px;color:var(--blue)}
        .actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:20px}
        .action{display:inline-flex;align-items:center;justify-content:center;min-height:45px;border-radius:13px;padding:0 18px;font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;font-weight:700;text-decoration:none;transition:transform .25s cubic-bezier(.2,.8,.2,1),box-shadow .25s ease}
        .action:hover{transform:translateY(-2px)}
        .action-primary{background:linear-gradient(115deg,var(--blue),var(--navy));color:#fff;box-shadow:0 10px 22px rgba(23,104,209,.2)}
        .action-secondary{border:1px solid var(--line);background:#fff;color:var(--ink)}
        .action-secondary:hover{box-shadow:0 8px 18px rgba(7,27,72,.08)}
        .error-footer{padding:9px 24px;border-top:1px solid var(--line);color:#8aa0ba;font-size:11px;text-align:center}
        @media(max-width:700px){body{align-items:flex-start;padding:48px 10px 10px}.error-shell{border-radius:22px}.error-top{padding:11px 18px}.status-pill{display:none}.error-body{grid-template-columns:1fr;gap:8px;padding:18px 22px 23px}.illustration{min-height:155px}.code{font-size:52px}.copy{text-align:center}.description{font-size:14px}.info{text-align:left;margin-top:13px;padding-top:10px;padding-bottom:10px}.actions{justify-content:center;margin-top:16px}.error-footer{padding:8px 18px}}
        @media(prefers-reduced-motion:reduce){*,*:before,*:after{scroll-behavior:auto!important;animation:none!important;transition:none!important}}
    </style>
</head>
<body>
    @php
        $registrationStatus = \App\Support\SpmbConfiguration::forAcademicYear()?->status;
        $isClosed = $registrationStatus === 'ditutup';
        $registrationKicker = $isClosed ? 'Pendaftaran sudah selesai' : 'Pendaftaran belum tersedia';
        $registrationTitle = $isClosed ? 'Pendaftaran sudah ditutup.' : 'Pendaftaran belum dibuka.';
        $registrationDescription = $isClosed
            ? 'Periode pendaftaran sudah berakhir dan saat ini tidak menerima pendaftar baru.'
            : 'Panitia masih menyiapkan pendaftaran. Silakan kembali lagi sesuai jadwal yang diumumkan.';
    @endphp
    <main class="error-shell">
        <header class="error-top">
            <a class="brand" href="{{ url('/') }}">
                <span class="brand-mark"><img src="{{ asset('images/logo-sekolah.png') }}" alt="Logo sekolah"></span>
                <span><strong>SPMB Online</strong><span>SMK Muhammadiyah 4 Cileungsi</span></span>
            </a>
            <span class="status-pill"><i></i> Informasi pendaftaran</span>
        </header>
        <section class="error-body">
            <div class="illustration" aria-hidden="true">
                <div class="code">403<small>Akses terbatas</small></div>
            </div>
            <div class="copy">
                <p class="kicker">{{ $registrationKicker }}</p>
                <h1>{{ $registrationTitle }}</h1>
                <p class="description">{{ $registrationDescription }}</p>
                <div class="info">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 10v6M12 7h.01"></path></svg>
                    <span>Jika kamu merasa seharusnya sudah bisa mendaftar, hubungi panitia atau periksa informasi terbaru dari sekolah.</span>
                </div>
                <div class="actions">
                    <a class="action action-primary" href="{{ url('/') }}">Kembali ke beranda</a>
                    <a class="action action-secondary" href="{{ route('login') }}">Masuk ke akun</a>
                </div>
            </div>
        </section>
        <footer class="error-footer">© {{ date('Y') }} <a href="https://sompetech.ilhamsompe5.workers.dev" target="_blank" rel="noopener noreferrer" style="display: inline; margin: 0; padding: 0; color: inherit; text-decoration: none; border: 0; outline: none; background: transparent; box-shadow: none;">Ilham Sompe &amp; Team.</a></footer>
    </main>
</body>
</html>
