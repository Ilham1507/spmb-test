# SMKM 4 Cileungsi — Patokan Desain Premium

Dokumen ini menjadi rujukan saat membuat atau merevisi halaman publik, SPMB, dan portal internal. Ambil keputusan desain dari aturan di bawah agar seluruh halaman terasa sebagai satu produk.

## Sumber utama

- Landing utama: `resources/views/landing/home.blade.php`
- Layout halaman publik: `resources/views/layouts/school.blade.php`
- Token global, interaksi, dan animasi: `resources/css/app.css`
- Header dan sidebar portal: `resources/views/components/portal-page-header.blade.php` dan `resources/views/components/portal-sidebar-brand.blade.php`

Jika ada aturan lama yang berbeda, dokumen ini dan landing page menjadi patokan visualnya.

## Tipografi

| Kebutuhan | Font | Berat | Catatan |
| --- | --- | --- | --- |
| Isi teks, navigasi, keterangan | `DM Sans` | 400–700 | Nyaman dibaca, dipakai sebagai font dasar. |
| Judul, angka penting, CTA, identitas | `Plus Jakarta Sans` | 600–800 | Dipakai untuk membuat hirarki yang tegas. |
| Fallback | `system-ui`, `sans-serif` | — | Jangan memakai font ketiga tanpa alasan kuat. |

Aturan ukuran:

- Kicker/label: 10–11px, huruf kapital, `letter-spacing: .16em` sampai `.20em`.
- Navigasi dan CTA: 12–13px, berat 700.
- Isi: 14–16px, `line-height: 1.6` sampai `1.75`.
- Judul kartu: 17–25px, `Plus Jakarta Sans` 700/800.
- Judul halaman: `clamp(38px, 5vw, 66px)`, `line-height` sekitar `1.02`, `letter-spacing: -.05em` sampai `-.065em`.

Jangan memakai huruf kapital penuh untuk paragraf atau judul besar. Label kecil saja yang memakai uppercase.

## Palet warna

| Nama | Nilai | Pemakaian |
| --- | --- | --- |
| Navy utama | `#092e6a` / `--brand-navy` | Judul, CTA utama, hero gelap, navigasi aktif Admin. |
| Navy dalam | `#071b48` / `--brand-navy-deep` | Footer dan gradien bagian gelap. |
| Biru aksi | `#1768d1` / `--brand-blue` | Link, label, fokus, elemen pendukung. |
| Kuning aksen | `#ffd248` / `--brand-gold` | Sorotan kecil, angka, detail dekoratif. |
| Teks utama | `#102a53` / `--brand-ink` | Isi dan judul pada latar terang. |
| Teks sekunder | `#6881a5` / `--brand-muted` | Deskripsi dan metadata. |
| Latar dasar | `#f7fbff` hingga `#f4f8fc` | Latar halaman terang. |
| Garis | `#d9e8f7` / `--brand-border` | Batas kartu, pemisah, input. |

Warna peran portal hanya mengubah aksen, bukan struktur:

- Admin: navy `#0b3b83`
- Panitia: ungu `#6941c6`
- Bendahara: emas `#b66a0b`
- Peserta: toska `#087a75`

Warna status tetap semantik: hijau untuk berhasil, amber untuk menunggu/peringatan, merah untuk penolakan atau aksi berbahaya.

## Layout dan komponen

- Gunakan lebar konten `min(1160px, calc(100% - 40px))` pada publik; mobile memakai `calc(100% - 36px)`.
- Navbar publik berbentuk kartu putih semi-transparan, tinggi 64–74px, radius 18–22px, `backdrop-filter: blur(14px)` dan shadow biru sangat tipis.
- Header portal tinggi 76px dan berada di dalam alur layout. Sidebar serta area judul dimulai tepat setelah header pada desktop, tanpa spacer, margin kosong, atau navbar `fixed`; sidebar menjadi drawer di mobile.
- Semua portal memakai satu pola sidebar: daftar menu kerja saja. Profil dan keluar berada pada menu akun di navbar.
- Menu sidebar yang aktif wajib memakai gradasi, bukan warna datar. Gunakan arah `115deg` dari warna aksen yang lebih terang menuju warna aksen yang lebih gelap, teks putih, ikon dengan latar putih transparan, dan shadow berwarna lembut.
- Gradasi aktif peran: Admin `#1768d1 → #0b3b83 → #07265d`; Panitia `#6941c6 → #46258e`; Bendahara `#b66a0b → #854600`; Peserta `#087a75 → #065652`.
- Kartu memakai radius 20–28px, border biru muda, latar putih, dan shadow halus. Jangan gunakan banyak jenis radius dalam satu halaman.
- Dashboard portal memakai urutan: hero operasional bergradasi sesuai peran, 3–4 kartu metrik, panel aktivitas, lalu akses cepat. Hero menggunakan judul singkat, satu kalimat konteks, dan maksimal dua aksi.
- Panel dashboard memakai radius 20–28px, border lembut, heading `Plus Jakarta Sans`, dan metadata `DM Sans`. Kartu akses cepat memakai nomor atau ikon kecil serta panah; jangan mengulang instruksi panjang.
- Gunakan ruang section 60–92px pada desktop dan 38–60px pada mobile.
- Gunakan grid seperlunya; pada lebar di bawah 800px kartu harus turun menjadi satu kolom atau dua kolom kecil yang masih terbaca.

### Tombol

- CTA utama: navy, teks putih, radius 12–14px, `Plus Jakarta Sans` 700.
- CTA sekunder: putih dengan border biru muda dan teks navy.
- CTA aksen: kuning hanya untuk tindakan pendukung atau sorotan, bukan semua tombol.
- Hover tombol: naik maksimum 2–4px dan shadow sedikit lebih tegas.

### Foto

- Gunakan foto dokumentasi asli sekolah sebagai sumber utama.
- Foto dalam kartu menggunakan `object-fit: cover`, radius mengikuti kartu, serta overlay navy transparan bila teks berada di atas foto.
- Hindari kolase kotak-kotak. Untuk gabungan foto, pakai komposisi menyatu dengan gradasi atau mask, dan pastikan empat konsentrasi keahlian terwakili bila konteksnya profil sekolah.

## Gerak dan interaksi

Animasi harus memperjelas hirarki, bukan menjadi hiasan berlebihan.

| Pola | Nilai patokan | Pemakaian |
| --- | --- | --- |
| Masuk halaman | `0.42s`, `translateY(8px)` | Konten global setelah halaman siap. |
| Reveal section | `0.95s`, `translateY(24px) scale(.985)` | Section landing; beri jeda 0.12s antar section. |
| Hero copy | `1s`, dari kiri 20px | Teks hero. |
| Hero visual | `1.15s`, dari kanan 24px dan scale `.97` | Gambar/visual hero. |
| Hover kartu | `0.3–0.4s`, naik 4–9px | Kartu informasi dan program. |
| Hover gambar | `0.65–1.2s`, scale maksimum `1.07` | Foto kartu atau hero. |
| Elemen dekoratif | `5–6s ease-in-out infinite` | Orbit atau elemen ringan saja. |

Gunakan easing `cubic-bezier(.16,1,.3,1)` untuk reveal dan `cubic-bezier(.2,.8,.2,1)` untuk hover. Jangan menggerakkan layout utama terus-menerus, jangan membuat carousel berhenti saat di-hover jika memang harus autoplay, dan jangan gunakan animasi yang mengubah tinggi konten.

Semua animasi wajib memiliki fallback berikut:

```css
@media (prefers-reduced-motion: reduce) {
  * { animation: none !important; transition: none !important; }
}
```

## Checklist revisi halaman

1. Pakai `DM Sans` untuk isi dan `Plus Jakarta Sans` untuk display/CTA.
2. Ambil warna dari token di atas; jangan menambah warna merek baru.
3. Pastikan kontras teks terhadap latar jelas, terutama pada kartu gelap dan gambar.
4. Gunakan header dan sidebar portal bersama, lalu bedakan hanya menu serta aksen peran.
5. Pastikan mobile tidak memotong teks, kartu, carousel, atau gambar.
6. Terapkan gerak yang singkat dan halus, serta patuhi `prefers-reduced-motion`.
7. Setelah revisi CSS/Blade, jalankan `npm run build` dan `php artisan view:cache`.

## Batas akses materi tes

- **Admin** mengelola konten tes: bank soal CBT (tambah, import, export, hapus) dan daftar pertanyaan wawancara orang tua (tambah, ubah, aktif/nonaktif, hapus).
- **Panitia** menjalankan operasional: memilih peserta, membuka akses CBT, melihat hasil CBT, memakai pertanyaan wawancara aktif, dan menyimpan ringkasan hasil wawancara.
- Jangan tampilkan formulir pengelolaan soal atau pertanyaan pada portal Panitia. Bila sebuah pertanyaan dinonaktifkan oleh Admin, pertanyaan tersebut tidak muncul pada sesi wawancara baru.

- Halaman laporan dan detail hasil tes memakai token `--portal-accent`, `--portal-accent-deep`, `--portal-soft`, dan `--portal-line`, sehingga warna filter, badge, tombol aksi, focus input, dan hover tabel mengikuti peran yang sedang login. Animasi masuk maksimal `0.46s`, dengan fallback `prefers-reduced-motion`.
