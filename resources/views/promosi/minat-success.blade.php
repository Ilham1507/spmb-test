<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terima kasih - SPMB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-[#eff8f4] p-5 font-['Plus_Jakarta_Sans'] text-slate-900">
    <main class="w-full max-w-lg overflow-hidden rounded-[28px] border border-emerald-100 bg-white shadow-[0_20px_60px_rgba(21,94,71,.14)]">
        <header class="bg-emerald-700 px-7 py-7 text-center text-white sm:px-10">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-white/20 bg-white/15 text-3xl">✓</span>
            <p class="mt-4 text-xs font-extrabold uppercase tracking-[.14em] text-emerald-100">Data berhasil dikirim</p>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">Terima kasih!</h1>
        </header>
        <div class="p-6 sm:p-8">
            <p class="text-center text-sm leading-6 text-slate-500">Data kamu sudah kami terima. Sambil menunggu informasi selanjutnya, yuk kenali sekolah lebih dekat.</p>
            <div class="mt-7">
                <p class="mb-3 text-center text-sm font-extrabold text-emerald-950">Mau lihat apa sekarang?</p>
                <div class="space-y-3">
                    <a href="{{ session('selected_major_url', route('jurusan')) }}" class="group flex items-center gap-4 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 transition hover:border-emerald-400 hover:bg-emerald-50">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-700 text-lg text-white">✦</span>
                        <span class="min-w-0 flex-1"><span class="block text-sm font-extrabold text-emerald-950">Jurusan yang kamu pilih</span><span class="mt-0.5 block truncate text-xs font-semibold text-emerald-700">{{ session('selected_major_name', 'Lihat semua pilihan jurusan') }}</span></span>
                        <span class="text-xl font-bold text-emerald-700 transition group-hover:translate-x-1">→</span>
                    </a>
                    <a href="{{ route('pengumuman') }}" class="group flex items-center gap-4 rounded-2xl border border-blue-100 bg-blue-50/70 p-4 transition hover:border-blue-300 hover:bg-blue-50">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-700 text-lg text-white">◌</span>
                        <span class="min-w-0 flex-1"><span class="block text-sm font-extrabold text-blue-950">Berita sekolah</span><span class="mt-0.5 block text-xs font-semibold text-blue-700">Kegiatan dan informasi terbaru</span></span>
                        <span class="text-xl font-bold text-blue-700 transition group-hover:translate-x-1">→</span>
                    </a>
                </div>
            </div>
            <a href="{{ route('home') }}" class="mt-6 flex w-full items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-extrabold text-slate-600 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800">Kembali ke beranda</a>
        </div>
    </main>
</body>
</html>
