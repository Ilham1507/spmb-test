<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $siteSettings['portal_name'] }} - {{ $siteSettings['school_short_name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; }
        [x-cloak] { display: none !important; }
        body { margin: 0; background: radial-gradient(circle at 8% 15%, rgba(255, 194, 0, .30), transparent 25%), radial-gradient(circle at 92% 85%, rgba(35, 111, 211, .36), transparent 30%), #09265d; color: #0b1f46; font-family: 'Poppins', 'Plus Jakarta Sans', system-ui, sans-serif; }
        .auth-page { align-items: center; display: flex; isolation: isolate; justify-content: center; min-height: 100dvh; overflow: hidden; padding: 32px 20px; position: relative; }
        .auth-page::before, .auth-page::after { border: 1px solid rgba(255, 255, 255, .15); border-radius: 999px; content: ''; pointer-events: none; position: absolute; z-index: -1; }
        .auth-page::before { height: 520px; right: -190px; top: -170px; width: 520px; }
        .auth-page::after { bottom: -260px; height: 600px; left: -260px; width: 600px; }
        .auth-ambient { background: radial-gradient(circle, rgba(36, 112, 207, .16), transparent 68%); filter: blur(4px); height: 480px; left: var(--glow-x, 50%); pointer-events: none; position: absolute; top: var(--glow-y, 35%); transform: translate(-50%, -50%); transition: left .25s ease-out, top .25s ease-out; width: 480px; z-index: -1; }
        .auth-shell { width: min(100%, 740px); }
        .auth-card { animation: auth-enter .65s cubic-bezier(.2,.75,.25,1) both; background: rgba(255, 255, 255, .86); backdrop-filter: blur(18px); border: 1px solid rgba(255, 255, 255, .9); border-radius: 26px; box-shadow: 0 30px 80px rgba(20, 58, 48, .14), 0 5px 18px rgba(20, 58, 48, .06); display: grid; grid-template-columns: .82fr 1fr; min-height: 440px; overflow: hidden; position: relative; }
        .auth-card { transform: perspective(1200px) rotateX(var(--card-x, 0deg)) rotateY(var(--card-y, 0deg)); transition: transform .22s ease-out, box-shadow .25s ease; }
        .auth-card:hover { box-shadow: 0 38px 92px rgba(7, 33, 82, .20), 0 7px 22px rgba(7, 33, 82, .08); }
        .auth-card::before { background: linear-gradient(90deg, #0a2d6b 0%, #1759a5 64%, #ffc400 100%); content: ''; height: 6px; inset: 0 0 auto; position: absolute; }
        .auth-card::after { content: none; }
        .auth-visual { background: linear-gradient(145deg, #041438 0%, #082866 52%, #0e5eaa 100%); color: #fff; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; padding: 30px; position: relative; }
        .auth-visual::before, .auth-visual::after { border: 1px solid rgba(255,255,255,.18); border-radius: 999px; content: ''; position: absolute; }
        .auth-visual::before { height: 390px; right: -190px; top: -100px; width: 390px; }
        .auth-visual::after { bottom: -210px; height: 430px; left: -180px; width: 430px; }
        .auth-visual-content, .auth-visual-footer { position: relative; z-index: 1; }
        .auth-visual-kicker { align-items: center; color: #ffd329; display: flex; font-size: 10px; font-weight: 700; gap: 8px; letter-spacing: .18em; text-transform: uppercase; }
        .auth-visual-kicker::before { background: #ffd329; border-radius: 99px; box-shadow: 0 0 14px rgba(255,211,41,.8); content: ''; height: 7px; width: 7px; }
        .auth-visual h2 { color: #fff; font-family: 'Poppins', sans-serif; font-size: 26px; font-weight: 700; letter-spacing: -.035em; line-height: 1.13; margin: 24px 0 10px; max-width: 300px; }
        .auth-visual-copy { color: rgba(255,255,255,.86); font-size: 12px; line-height: 1.6; max-width: 290px; }
        .auth-visual-mark { align-items: center; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.24); border-radius: 16px; box-shadow: 0 18px 30px rgba(0,0,0,.12); display: flex; height: 60px; justify-content: center; margin-top: 24px; transform: rotate(-4deg); width: 60px; }
        .auth-visual-mark img { background: #fff; border-radius: 12px; height: 46px; object-fit: contain; padding: 6px; width: 46px; }
        .auth-visual-footer { align-items: center; display: flex; gap: 10px; }
        .auth-visual-footer span { background: #ffd329; border-radius: 99px; box-shadow: 0 0 12px rgba(255,211,41,.65); height: 8px; width: 8px; }
        .auth-visual-footer p { color: rgba(255,255,255,.82); font-size: 11px; margin: 0; }
        .auth-form-panel { display: flex; flex-direction: column; justify-content: center; padding: 30px 36px 20px; position: relative; z-index: 1; }
        .auth-brand { align-items: center; color: #0b3d82; display: inline-flex; gap: 10px; text-decoration: none; }
        .auth-brand img { background: #fff; border: 1px solid #dbeae5; border-radius: 13px; box-shadow: 0 8px 18px rgba(8, 122, 97, .10); height: 44px; object-fit: contain; padding: 5px; width: 44px; }
        .auth-brand strong { display: block; font-size: 15px; font-weight: 700; letter-spacing: -.015em; }
        .auth-brand small { color: #71827d; display: block; font-size: 10px; font-weight: 700; letter-spacing: .09em; margin-top: 1px; text-transform: uppercase; }
        .auth-heading { margin: 20px 0 16px; }
        .auth-heading h1 { color: #071b48; font-family: 'Poppins', sans-serif; font-size: 24px; font-weight: 700; letter-spacing: -.035em; line-height: 1.12; margin: 0; }
        .auth-heading p { color: #536a63; font-size: 13px; margin: 5px 0 0; }
        .auth-form { display: grid; gap: 16px; }
        .auth-form > div, .auth-form > label, .auth-form > button, .auth-form > p { will-change: transform, opacity; }
        .auth-form.is-submitting { pointer-events: none; }
        .auth-form.is-submitting .auth-button { opacity: .86; }
        .auth-form.is-submitting .auth-button::after { animation: auth-spin .7s linear infinite; border: 2px solid rgba(255,255,255,.45); border-right-color: #fff; border-radius: 999px; content: ''; height: 16px; position: absolute; right: 18px; top: 50%; transform: translateY(-50%); width: 16px; }
        .auth-has-errors .auth-card { animation: auth-enter .65s cubic-bezier(.2,.75,.25,1) both, auth-shake .42s ease-out 0.68s; }
        .auth-register .register-identity { display: grid; gap: 12px; grid-template-columns: 1fr 1fr; }
        .auth-form label { color: #203d35 !important; font-size: 12px !important; font-weight: 700 !important; letter-spacing: .005em; }
        .auth-password-row { align-items: center; display: flex; gap: 12px; justify-content: space-between; margin-bottom: 6px; }
        .auth-password-field { position: relative; }
        .auth-form input:not([type="checkbox"]):not([type="radio"]) { background: #f7fbf9 !important; border: 1.5px solid #a9c8be !important; border-radius: 14px !important; box-shadow: inset 0 1px 1px rgba(255,255,255,.7) !important; color: #10241f !important; display: block !important; font-family: inherit !important; font-size: 14px !important; font-weight: 600 !important; min-height: 44px !important; padding: 11px 15px !important; transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease !important; width: 100% !important; }
        .auth-form input:not([type="checkbox"]):not([type="radio"]):focus { background: #fff !important; border-color: #1764b0 !important; box-shadow: 0 0 0 4px rgba(23, 100, 176, .14), 0 8px 20px rgba(11, 45, 107, .09) !important; outline: none !important; transform: translateY(-1px); }
        .auth-form .auth-input-password { padding-right: 48px !important; }
        .auth-password-toggle { align-items: center; background: #eef4ff; border: 0; border-radius: 9px; color: #0b4f98; cursor: pointer; display: flex; height: 34px; justify-content: center; padding: 0; position: absolute; right: 7px; top: 50%; transform: translateY(-50%); width: 34px; z-index: 2; }
        .auth-password-toggle:hover { background: #eef4ff; color: #0b4f98; }
        .auth-password-toggle:focus-visible { outline: 3px solid rgba(7, 147, 111, .18); outline-offset: 1px; }
        .auth-link { color: #0b5eb7; font-size: 12px; font-weight: 700; letter-spacing: .005em; text-decoration: none; }
        .auth-link:hover { color: #08265e; text-decoration: underline; }
        .auth-button { background: linear-gradient(135deg, #0b2d6b, #1263ae); border: 0; border-radius: 14px; box-shadow: 0 12px 24px rgba(11, 45, 107, .28); color: #fff; cursor: pointer; font-family: inherit; font-size: 14px; font-weight: 700; letter-spacing: .01em; min-height: 44px; transition: background .2s ease, transform .2s ease, box-shadow .2s ease; width: 100%; }
        .auth-button { overflow: hidden; position: relative; }
        .auth-button::before { background: linear-gradient(110deg, transparent 20%, rgba(255,255,255,.35) 48%, transparent 75%); content: ''; inset: 0; position: absolute; transform: translateX(-130%); transition: transform .65s ease; }
        .auth-button:hover::before { transform: translateX(130%); }
        .auth-ripple { animation: auth-ripple .55s ease-out forwards; background: rgba(255,255,255,.42); border-radius: 999px; pointer-events: none; position: absolute; transform: scale(0); }
        .auth-button:hover { background: linear-gradient(135deg, #08265e, #0b4f98); box-shadow: 0 14px 26px rgba(11, 45, 107, .34); transform: translateY(-1px); }
        .auth-button:disabled { cursor: wait; opacity: .7; transform: none; }
        .auth-footer { color: #8b9995; font-size: 11px; margin-top: 22px; text-align: center; }
        @keyframes auth-enter { from { opacity: 0; transform: translateY(18px) scale(.985); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @keyframes auth-field-enter { from { opacity: 0; transform: translateY(9px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes auth-ripple { to { opacity: 0; transform: scale(3.5); } }
        @keyframes auth-spin { to { transform: translateY(-50%) rotate(360deg); } }
        @keyframes auth-shake { 20%, 60% { transform: perspective(1200px) translateX(-5px); } 40%, 80% { transform: perspective(1200px) translateX(5px); } }
        @media (max-width: 760px) { .auth-card { background: #fff; display: block; min-height: 0; padding-top: 0; } .auth-visual { display: none; } .auth-form-panel { background: #fff; border-radius: 25px; padding: 32px 30px 25px; } }
        @media (max-width: 520px) { .auth-page { padding: 12px 10px; } .auth-card { border-radius: 23px; } .auth-form-panel { padding: 27px 22px 23px; } .auth-heading { margin: 25px 0 20px; } .auth-heading h1 { font-size: 29px; } .auth-register .register-identity { grid-template-columns: 1fr; gap: 16px; } .auth-footer { margin-top: 10px; } }
        @media (prefers-reduced-motion: reduce) { .auth-card, .auth-ambient { animation: none; transition: none; } .auth-button, .auth-form input { transition: none !important; } }
    </style>
</head>
<body>
    @php($activeEvents = \App\Support\PromotionEvent::activeAnnouncements())
    <main class="auth-page {{ $errors->any() ? 'auth-has-errors' : '' }}"><div class="auth-ambient"></div><section class="auth-shell"><div class="auth-card">
        <aside class="auth-visual">
            <div class="auth-visual-content">
                <div class="auth-visual-kicker">Portal SPMB Online</div>
                <div class="auth-visual-mark"><img src="{{ asset($siteSettings['school_logo']) }}" alt=""></div>
                <h2>Langkah awal menuju masa depan.</h2>
                <p class="auth-visual-copy">Kelola pendaftaran dengan tenang, terarah, dan transparan dalam satu portal sekolah.</p>
            </div>
            <div class="auth-visual-footer"><span></span><p>{{ $siteSettings['school_name'] }} · Tahun Pelajaran {{ $siteSettings['academic_year'] ?? '2026/2027' }}</p></div>
        </aside>
        <div class="auth-form-panel">
            <a href="/" class="auth-brand"><img src="{{ asset($siteSettings['school_logo']) }}" alt="Logo {{ $siteSettings['school_short_name'] }}"><span><strong>{{ $siteSettings['school_short_name'] }}</strong><small>{{ $siteSettings['portal_name'] }}</small></span></a>
            <header class="auth-heading"><h1>{{ $auth_title ?? 'Selamat datang' }}</h1>@if(!empty($auth_subtitle))<p>{{ $auth_subtitle }}</p>@endif</header>
            {{ $slot }}
        </div>
    </div><div class="auth-footer">© {{ date('Y') }} <a href="https://sompetech.ilhamsompe5.workers.dev" target="_blank" rel="noopener noreferrer" style="display: inline; margin: 0; padding: 0; color: inherit; text-decoration: none; border: 0; outline: none; background: transparent; box-shadow: none;">Ilham Sompe &amp; Team.</a></div></section></main>
    @if($activeEvents)
        <div x-data="{ show: true }" x-show="show" x-cloak class="fixed inset-0 z-[300] flex items-center justify-center bg-slate-950/45 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl" @click.outside="show=false">
                <div class="flex items-start justify-between gap-3"><div><p class="text-[11px] font-black uppercase tracking-widest text-emerald-600">Event aktif</p><h2 class="mt-1 text-xl font-black text-slate-950">{{ $activeEvents[0]['name'] ?? 'Promo terbaru' }}</h2></div><button type="button" @click="show=false" class="text-xl text-slate-400">&times;</button></div>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">Dapatkan potongan {{ ($activeEvents[0]['discount_type'] ?? '') === 'percent' ? rtrim(rtrim(number_format((float) ($activeEvents[0]['discount_value'] ?? 0), 2, ',', '.'), '0'), ',').'%' : 'Rp '.number_format((float) ($activeEvents[0]['discount_value'] ?? 0), 0, ',', '.') }} untuk {{ ($activeEvents[0]['target'] ?? '') === 'formulir' ? 'biaya formulir' : (($activeEvents[0]['target'] ?? '') === 'daftar_ulang' ? 'daftar ulang' : 'tagihan SPMB') }}.</p>
                <p class="mt-2 text-xs font-semibold text-slate-400">Berlaku {{ $activeEvents[0]['starts_at'] ?? '-' }} sampai {{ $activeEvents[0]['ends_at'] ?? '-' }}</p>
                <button type="button" @click="show=false" class="auth-button mt-5">Lanjut</button>
            </div>
        </div>
    @endif
    <x-global-loading />
    <script>
        (() => {
            const page = document.querySelector('.auth-page');
            if (!page || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const panel = page.querySelector('.auth-form-panel');
            const form = page.querySelector('.auth-form');
            const button = page.querySelector('.auth-button');
            if (panel) {
                panel.querySelectorAll('.auth-form > div, .auth-form > label, .auth-form > button, .auth-form > p').forEach((item, index) => {
                    item.style.animation = 'auth-field-enter .45s cubic-bezier(.2,.75,.25,1) both';
                    item.style.animationDelay = (index * 55 + 180) + 'ms';
                });
            }
            if (form) {
                form.addEventListener('submit', () => {
                    form.classList.add('is-submitting');
                    const submit = form.querySelector('button[type="submit"]');
                    if (submit) submit.setAttribute('aria-busy', 'true');
                }, { once: true });
            }
            const card = page.querySelector('.auth-card');
            if (card && window.matchMedia('(min-width: 761px)').matches) {
                page.addEventListener('pointermove', (event) => {
                    const rect = card.getBoundingClientRect();
                    const x = ((event.clientX - rect.left) / rect.width - .5) * 1.4;
                    const y = ((event.clientY - rect.top) / rect.height - .5) * -1.4;
                    card.style.setProperty('--card-y', x.toFixed(2) + 'deg');
                    card.style.setProperty('--card-x', y.toFixed(2) + 'deg');
                }, { passive: true });
                page.addEventListener('pointerleave', () => {
                    card.style.setProperty('--card-x', '0deg');
                    card.style.setProperty('--card-y', '0deg');
                });
            }
            page.addEventListener('pointermove', (event) => {
                page.style.setProperty('--glow-x', event.clientX + 'px');
                page.style.setProperty('--glow-y', event.clientY + 'px');
            }, { passive: true });
            if (button) {
                button.addEventListener('click', (event) => {
                    const rect = button.getBoundingClientRect();
                    const ripple = document.createElement('span');
                    const size = Math.max(rect.width, rect.height);
                    ripple.className = 'auth-ripple';
                    ripple.style.width = size + 'px';
                    ripple.style.height = size + 'px';
                    ripple.style.left = (event.clientX - rect.left - size / 2) + 'px';
                    ripple.style.top = (event.clientY - rect.top - size / 2) + 'px';
                    button.appendChild(ripple);
                    window.setTimeout(() => ripple.remove(), 600);
                });
            }
        })();
    </script>
</body>
</html>
