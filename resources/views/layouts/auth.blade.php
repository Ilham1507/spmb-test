<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Portal Siswa' }} · SMK Negeri</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    <style>
        :root{--ink:#152b2a;--muted:#71817f;--line:#dfe9e6;--brand:#087f6b;--brand-dark:#056351;--soft:#f5faf8}
        *{box-sizing:border-box}html,body{min-height:100%;margin:0}body{font-family:'Instrument Sans',Inter,ui-sans-serif,system-ui,sans-serif;color:var(--ink);background:var(--soft);overflow-x:hidden}
        .auth-shell{min-height:100vh;display:grid;place-items:center;padding:32px 20px;position:relative;isolation:isolate}
        .glow{position:fixed;border-radius:999px;filter:blur(2px);z-index:-1;pointer-events:none;animation:float 9s ease-in-out infinite}.glow.one{width:340px;height:340px;background:#d8f2e9;top:-170px;right:-95px}.glow.two{width:260px;height:260px;background:#e5f3f6;bottom:-110px;left:-90px;animation-delay:-3s}
        .auth-card{width:min(100%,430px);background:#fff;border:1px solid rgba(210,226,221,.8);border-radius:28px;padding:42px 44px 34px;box-shadow:0 24px 70px rgba(21,62,55,.10);animation:rise .65s cubic-bezier(.2,.75,.25,1) both}
        .brand{display:flex;align-items:center;gap:12px;margin-bottom:34px}.brand-mark{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:var(--ink);color:#fff;font-weight:700;letter-spacing:-.06em;box-shadow:0 8px 18px rgba(21,43,42,.16)}.brand-copy{display:grid;gap:2px}.brand-copy strong{font-size:15px;letter-spacing:-.02em}.brand-copy span{color:var(--muted);font-size:11px;letter-spacing:.13em;text-transform:uppercase;font-weight:600}
        h1{font-size:32px;line-height:1.1;letter-spacing:-.055em;margin:0 0 10px}.intro{color:var(--muted);font-size:15px;line-height:1.55;margin:0 0 28px}.field{margin-bottom:18px}.field-label{display:block;font-size:13px;font-weight:600;margin:0 0 8px;color:#36514d}.input-wrap{position:relative}.input-wrap svg{position:absolute;width:18px;height:18px;left:15px;top:50%;transform:translateY(-50%);color:#96aaa5;transition:color .2s}.input-wrap input{width:100%;height:52px;border:1px solid var(--line);border-radius:14px;padding:0 16px 0 46px;outline:0;background:#fbfdfc;color:var(--ink);font:inherit;font-size:15px;transition:border-color .2s,box-shadow .2s,background .2s}.input-wrap input::placeholder{color:#a5b2af}.input-wrap input:focus{border-color:#22a78e;background:#fff;box-shadow:0 0 0 4px rgba(34,167,142,.12)}.input-wrap:focus-within svg{color:var(--brand)}
        .password-toggle{border:0;background:transparent;color:#8da09c;cursor:pointer;position:absolute;right:11px;top:50%;transform:translateY(-50%);padding:8px}.password-toggle:hover{color:var(--brand)}.password-toggle svg{position:static;transform:none;width:17px;height:17px}.password-toggle .eye-off{display:none}.password-toggle.is-visible .eye{display:none}.password-toggle.is-visible .eye-off{display:block}
        .field-row{display:flex;justify-content:space-between;align-items:center;gap:10px;margin:-2px 0 24px;font-size:13px}.check{display:flex;align-items:center;gap:8px;color:#627470;cursor:pointer}.check input{accent-color:var(--brand);width:16px;height:16px;margin:0}.link{color:var(--brand);font-weight:600;text-decoration:none}.link:hover{text-decoration:underline}.submit{width:100%;height:52px;border:0;border-radius:14px;background:var(--brand);color:#fff;font:inherit;font-weight:700;font-size:15px;cursor:pointer;box-shadow:0 12px 22px rgba(8,127,107,.18);transition:transform .2s,background .2s,box-shadow .2s}.submit:hover{background:var(--brand-dark);transform:translateY(-2px);box-shadow:0 15px 26px rgba(8,127,107,.25)}.submit:active{transform:translateY(0)}.switch{text-align:center;color:var(--muted);font-size:13px;margin:24px 0 0}.error{font-size:12px;color:#bd423b;margin-top:7px}.alert{font-size:13px;color:#9d3c35;background:#fff3f1;border-radius:10px;padding:11px 12px;margin-bottom:18px}
        @keyframes rise{from{opacity:0;transform:translateY(16px) scale(.98)}to{opacity:1;transform:none}}@keyframes float{0%,100%{transform:translate(0,0)}50%{transform:translate(0,18px)}}@media(max-width:480px){.auth-shell{padding:20px 16px}.auth-card{padding:32px 24px 26px;border-radius:22px}.brand{margin-bottom:28px}h1{font-size:29px}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;transition-duration:.01ms!important}}
    </style>
</head>
<body>
    <main class="auth-shell"><div class="glow one"></div><div class="glow two"></div>
        <section class="auth-card">{{ $slot ?? '' }} @yield('content')</section>
    </main>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((button)=>button.addEventListener('click',()=>{const input=document.getElementById(button.dataset.passwordToggle);const visible=input.type==='text';input.type=visible?'password':'text';button.classList.toggle('is-visible',!visible);button.setAttribute('aria-label',visible?'Tampilkan kata sandi':'Sembunyikan kata sandi')}));
    </script>
</body>
</html>
