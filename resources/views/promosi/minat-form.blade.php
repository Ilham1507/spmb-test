<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPMB - SMK Muhammadiyah 4 Cileungsi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#eff8f4] font-['Plus_Jakarta_Sans'] text-slate-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-6 sm:px-6 sm:py-10">
        <section class="grid w-full max-w-6xl overflow-hidden rounded-[28px] border border-emerald-100 bg-white shadow-[0_24px_70px_rgba(21,94,71,.15)] lg:grid-cols-[.82fr_1.18fr]">
            <aside class="relative hidden min-h-[650px] overflow-hidden bg-gradient-to-br from-[#064e3b] via-[#087a5b] to-[#0b9b70] p-10 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="absolute -right-36 -top-32 h-96 w-96 rounded-full border border-white/20"></div><div class="absolute -bottom-40 -left-36 h-[28rem] w-[28rem] rounded-full border border-white/20"></div>
                <div class="relative"><p class="flex items-center gap-2 text-xs font-black uppercase tracking-[.2em] text-emerald-100"><span class="h-2 w-2 rounded-full bg-amber-300"></span> SPMB Online</p><div class="mt-9 flex h-16 w-16 items-center justify-center rounded-2xl border border-white/25 bg-white/15 p-2 shadow-xl"><img src="{{ asset('images/logo-sekolah.png') }}" alt="Logo sekolah" class="h-full w-full rounded-xl bg-white object-contain p-1"></div><h1 class="mt-8 max-w-sm text-4xl font-extrabold leading-[1.12] tracking-tight">Mulai langkahmu menuju masa depan.</h1><p class="mt-5 max-w-sm text-sm leading-7 text-emerald-50">Terima kasih sudah mampir saat kegiatan promosi. Isi data kamu sebentar untuk informasi SPMB.</p></div>
                <div class="relative flex items-center gap-3 text-xs font-semibold text-emerald-50"><span class="h-2 w-2 rounded-full bg-amber-300"></span><span>SMK Muhammadiyah 4 Cileungsi<br>Tahun Pelajaran 2027/2028</span></div>
            </aside>
            <div class="p-6 sm:p-9 lg:p-10">
                <div class="flex items-center gap-3 lg:hidden"><span class="flex h-11 w-11 items-center justify-center rounded-xl border border-emerald-100 bg-emerald-50 p-1"><img src="{{ asset('images/logo-sekolah.png') }}" alt="Logo sekolah" class="h-full w-full object-contain"></span><div><p class="text-sm font-extrabold text-emerald-900">SMKM 4 Cileungsi</p><p class="text-[10px] font-bold uppercase tracking-[.12em] text-emerald-600">SPMB Online</p></div></div>
                <header class="mt-8 lg:mt-0"><p class="text-xs font-black uppercase tracking-[.16em] text-emerald-600">SPMB SMK Muhammadiyah 4 Cileungsi</p><h2 class="mt-2 text-3xl font-extrabold tracking-tight text-emerald-950">Yuk, isi data kamu.</h2><p class="mt-3 max-w-xl text-sm leading-6 text-slate-500">Lengkapi data singkat berikut untuk informasi SPMB.</p></header>
                <form method="POST" action="{{ route('promosi.minat.store') }}" class="mt-7 space-y-5" autocomplete="on">
                    @csrf
                    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>@endif
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-bold text-emerald-950 sm:col-span-2">Nama lengkap<input name="full_name" value="{{ old('full_name') }}" required maxlength="150" class="mt-2 w-full rounded-xl border border-emerald-200 bg-emerald-50/40 px-4 py-3 text-sm font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100" placeholder="Tulis nama lengkapmu"></label>
                        <label class="block text-sm font-bold text-emerald-950 sm:col-span-2">Nomor WhatsApp siswa<input name="student_phone" value="{{ old('student_phone') }}" required inputmode="numeric" class="mt-2 w-full rounded-xl border border-emerald-200 bg-emerald-50/40 px-4 py-3 text-sm font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100" placeholder="08xxxxxxxxxx"></label>
                        <label class="block text-sm font-bold text-emerald-950 sm:col-span-2">SMP/MTs saat ini<input name="school_name" value="{{ old('school_name') }}" required maxlength="180" class="mt-2 w-full rounded-xl border border-emerald-200 bg-emerald-50/40 px-4 py-3 text-sm font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100" placeholder="Tulis nama SMP/MTs kamu"></label>
                        <label class="block text-sm font-bold text-emerald-950 sm:col-span-2">Jurusan yang kamu minati <span class="font-medium text-slate-400">(opsional)</span><span class="relative mt-2 block"><select name="interested_major_id" class="block w-full appearance-none rounded-xl border border-emerald-200 bg-emerald-50/40 px-4 py-3 pr-12 text-sm font-semibold text-slate-800 outline-none transition focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100"><option value="">Pilih jurusan</option>@foreach($jurusans as $jurusan)<option value="{{ $jurusan->id }}" @selected((string) old('interested_major_id') === (string) $jurusan->id)>{{ $jurusan->name }}</option>@endforeach</select><svg class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg></span></label>
                    </div>
                    <button class="w-full rounded-xl bg-emerald-700 px-5 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-200">Simpan data →</button>
                    <p class="text-center text-[11px] leading-relaxed text-slate-400">Data kamu disimpan dengan aman oleh SMK Muhammadiyah 4 Cileungsi.</p>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
