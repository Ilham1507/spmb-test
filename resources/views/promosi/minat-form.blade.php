<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minat SPMB - SMK Muhammadiyah 4 Cileungsi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-950 via-blue-800 to-teal-700 px-4 py-8 text-slate-900 sm:py-12">
    <main class="mx-auto max-w-2xl overflow-hidden rounded-[2rem] bg-white shadow-2xl shadow-blue-950/30">
        <section class="bg-gradient-to-r from-blue-900 to-teal-700 px-6 py-8 text-white sm:px-10">
            <p class="text-xs font-black uppercase tracking-[.2em] text-teal-100">SPMB SMK Muhammadiyah 4 Cileungsi</p>
            <h1 class="mt-3 text-3xl font-black leading-tight sm:text-4xl">Tertarik menjadi bagian dari kami?</h1>
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-blue-100 sm:text-base">Isi data singkat ini saat kegiatan promosi. Tim sekolah akan menyimpan minatmu dan siap membantu saat pendaftaran dibuka.</p>
        </section>
        <form method="POST" action="{{ route('promosi.minat.store') }}" class="space-y-5 px-6 py-7 sm:px-10 sm:py-9" autocomplete="on">
            @csrf
            @if($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
            @endif
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-extrabold text-slate-700 sm:col-span-2">Nama lengkap
                    <input name="full_name" value="{{ old('full_name') }}" required maxlength="150" class="mt-2 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-base outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-100" placeholder="Tulis nama lengkapmu">
                </label>
                <label class="block text-sm font-extrabold text-slate-700">Nomor WhatsApp siswa
                    <input name="student_phone" value="{{ old('student_phone') }}" required inputmode="numeric" class="mt-2 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-base outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-100" placeholder="08xxxxxxxxxx">
                </label>
                <label class="block text-sm font-extrabold text-slate-700">Nomor HP orang tua <span class="font-medium text-slate-400">(opsional)</span>
                    <input name="parent_phone" value="{{ old('parent_phone') }}" inputmode="numeric" class="mt-2 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-base outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-100" placeholder="08xxxxxxxxxx">
                </label>
                <label class="block text-sm font-extrabold text-slate-700 sm:col-span-2">SMP/MTs saat ini
                    <input name="school_name" value="{{ old('school_name') }}" required maxlength="180" class="mt-2 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-base outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-100" placeholder="Contoh: SMP Negeri 1 Cileungsi">
                </label>
                <label class="block text-sm font-extrabold text-slate-700">Kelas saat ini <span class="font-medium text-slate-400">(opsional)</span>
                    <select name="class_level" class="mt-2 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-base outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-100"><option value="">Pilih kelas</option><option value="VII" @selected(old('class_level') === 'VII')>Kelas VII</option><option value="VIII" @selected(old('class_level') === 'VIII')>Kelas VIII</option><option value="IX" @selected(old('class_level') === 'IX')>Kelas IX</option></select>
                </label>
                <label class="block text-sm font-extrabold text-slate-700">Jurusan yang diminati <span class="font-medium text-slate-400">(opsional)</span>
                    <select name="interested_major_id" class="mt-2 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-base outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-100"><option value="">Belum menentukan</option>@foreach($jurusans as $jurusan)<option value="{{ $jurusan->id }}" @selected((string) old('interested_major_id') === (string) $jurusan->id)>{{ $jurusan->name }}</option>@endforeach</select>
                </label>
                <label class="block text-sm font-extrabold text-slate-700 sm:col-span-2">Catatan atau pertanyaan <span class="font-medium text-slate-400">(opsional)</span>
                    <textarea name="promotion_note" rows="3" maxlength="500" class="mt-2 w-full resize-y rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-base outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-100" placeholder="Contoh: ingin tahu biaya atau kegiatan sekolah">{{ old('promotion_note') }}</textarea>
                </label>
            </div>
            <button class="w-full rounded-xl bg-teal-700 px-5 py-4 text-base font-black text-white shadow-lg shadow-teal-200 transition hover:bg-teal-800 focus:outline-none focus:ring-4 focus:ring-teal-200">Simpan minat saya</button>
            <p class="text-center text-xs leading-relaxed text-slate-500">Data digunakan oleh tim SPMB SMK Muhammadiyah 4 Cileungsi untuk tindak lanjut informasi pendaftaran.</p>
        </form>
    </main>
</body>
</html>
