<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terima kasih - SPMB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-[#eff8f4] p-5 font-['Plus_Jakarta_Sans']">
    <main class="w-full max-w-xl overflow-hidden rounded-[28px] border border-emerald-100 bg-white shadow-[0_24px_70px_rgba(21,94,71,.15)]">
        <div class="bg-gradient-to-br from-emerald-900 to-emerald-600 px-8 py-8 text-center text-white sm:px-10">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-white/20 bg-white/15 text-3xl">✓</span>
            <p class="mt-5 text-xs font-black uppercase tracking-[.16em] text-emerald-100">Data tersimpan</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Terima kasih sudah mengisi.</h1>
        </div>
        <div class="p-7 text-center sm:p-10">
            <p class="mx-auto max-w-md text-sm leading-7 text-slate-500">Data kamu sudah kami terima. Sambil menunggu informasi SPMB berikutnya, kamu bisa mengenal sekolah lebih dekat.</p>
            <div class="mt-7 grid gap-3 text-left sm:grid-cols-2">
                <a href="{{ session('selected_major_url', route('jurusan')) }}" class="group rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 transition hover:border-emerald-400 hover:bg-emerald-50">
                    <span class="text-xs font-black uppercase tracking-[.13em] text-emerald-700">Pilihan jurusan</span>
                    <span class="mt-2 block text-base font-extrabold text-emerald-950">{{ session('selected_major_name') ? 'Lihat '.session('selected_major_name') : 'Lihat jurusan yang kamu minati' }}</span>
                    <span class="mt-2 block text-sm font-bold text-emerald-700">Lihat detail →</span>
                </a>
                <a href="{{ route('pengumuman') }}" class="group rounded-2xl border border-blue-100 bg-blue-50/60 p-5 transition hover:border-blue-300 hover:bg-blue-50">
                    <span class="text-xs font-black uppercase tracking-[.13em] text-blue-700">Informasi sekolah</span>
                    <span class="mt-2 block text-base font-extrabold text-blue-950">Lihat berita dan kegiatan sekolah</span>
                    <span class="mt-2 block text-sm font-bold text-blue-700">Buka informasi →</span>
                </a>
            </div>
            <a href="{{ route('home') }}" class="mt-7 inline-flex text-sm font-extrabold text-slate-500 underline-offset-4 hover:text-emerald-700 hover:underline">Kembali ke beranda</a>
        </div>
    </main>
</body>
</html>
