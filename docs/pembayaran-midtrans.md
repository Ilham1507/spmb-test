# Pembayaran sekolah melalui virtual account

Integrasi menggunakan **Midtrans Snap Redirect**, dengan pilihan bank VA saja. Siswa membuka tagihan lalu menekan **Bayar sekarang**. Nominal pelunasan dihitung server; pemilihan bank dan instruksi transfer ada di halaman Midtrans. Rincian biaya dan pembayaran terakhir tetap tersedia di aplikasi sekolah.

**Status pemasangan:** kode tersedia, tetapi gateway secara bawaan tidak aktif. Tidak ada akun merchant, Server Key, atau transaksi sungguhan yang dibuat oleh perubahan ini. Pengujian otomatis menggunakan database SQLite sementara dan respons HTTP tiruan. Uji nyata di sandbox dan aktivasi merchant tetap diperlukan sebelum produksi.

## Menyiapkan sandbox

1. Buat akun sekolah melalui [dashboard resmi Midtrans](https://dashboard.midtrans.com/). Gunakan akun yang dikelola sekolah dengan akses bendahara yang sesuai.
2. Gunakan **database pengujian terpisah dari data siswa sungguhan**. Pembayaran simulasi yang berhasil akan melunasi tagihan di database pengujian tersebut.
3. Salin konfigurasi berikut ke `.env` pada lingkungan uji. Isi nilai asli langsung di server; jangan mengirim kunci lewat chat, memasukkannya ke Git, atau menaruhnya pada `VITE_*`.

   ```dotenv
   MIDTRANS_ENABLED=true
   MIDTRANS_PRODUCTION=false
   MIDTRANS_SERVER_KEY=
   MIDTRANS_MERCHANT_ID=
   ```

4. Jalankan migrasi tambahan, lalu muat ulang konfigurasi:

   ```shell
   php artisan migrate --path=database/migrations/2026_09_05_160000_create_payment_checkouts_table.php
   php artisan config:clear
   npm run build
   ```

5. Atur Payment Notification URL di dashboard Midtrans menjadi `https://DOMAIN-SEKOLAH/api/webhooks/midtrans`. URL ini harus dapat diakses Midtrans menggunakan HTTPS. Alamat lokal seperti `smk.test` tidak dapat menerima notifikasi dari internet. Gunakan staging publik untuk uji callback; pastikan URL kembali menggunakan domain staging yang benar.
6. Aktifkan bank VA yang disetujui pada akun merchant. Daftar kanal pada `config/payments.php` adalah BCA, BNI, BRI, Permata, dan Mandiri bill payment; sesuaikan dengan kanal yang tersedia pada akun. Biaya layanan dan persyaratan aktivasi mengikuti persetujuan merchant. Lihat [informasi layanan Midtrans](https://midtrans.com/id/produk/online-payment).
7. Uji pembayaran sukses, kedaluwarsa, koneksi terputus, klik ganda, pengiriman ulang notifikasi, serta pencocokan laporan. Gunakan simulator sesuai [panduan integrasi resmi](https://docs.midtrans.com/docs/snap-snap-integration-guide).

## Cara status lunas ditentukan

- Kunci hanya digunakan backend melalui HTTPS Basic Authentication ke host Midtrans yang tetap.
- Webhook diperiksa dengan SHA-512 dan perbandingan konstan, lalu server meminta **Get Transaction Status** secara mandiri. Status dari browser dan query URL kembali tidak mengubah tagihan.
- Order ID, merchant ID, lingkungan, mata uang IDR, nominal, metode VA, dan status settlement harus cocok. Capture, challenge, atau status yang tidak dikenal tidak otomatis melunasi tagihan.
- Satu order aktif per tagihan dijaga oleh indeks unik dan transaksi database. Klik ulang memakai order yang sama. Jika pembuatan order mengalami timeout, aplikasi tidak membuat order pengganti secara otomatis.
- Pencatatan pembayaran dan pengurangan tagihan dilakukan dalam satu transaksi dengan penguncian baris. Notifikasi berulang tidak menambah pembayaran dua kali.
- Tagihan yang berubah atau refund/chargeback masuk daftar **Perlu rekonsiliasi**. Koreksi keuangan tidak dilakukan diam-diam.

Rujukan teknis: [verifikasi notifikasi](https://docs.midtrans.com/docs/https-notification-webhooks), [status transaksi](https://docs.midtrans.com/reference/get-transaction-status), dan [parameter Snap](https://docs.midtrans.com/reference/json-objects).

## Pemeriksaan oleh bendahara

Menu Pembayaran admin/bendahara menampilkan order VA yang belum selesai dan yang perlu rekonsiliasi. **Cek ke Midtrans** mengambil status penyedia; tombol ini bukan persetujuan manual. Pembayaran VA yang telah diterima masuk riwayat pembayaran yang sama dengan transaksi lainnya.

Jika webhook terlambat, siswa dapat menggunakan **Cek status**, atau operator menjalankan:

```shell
php artisan payments:reconcile
php artisan payments:reconcile --order=SPMB-ID-TRANSAKSI
```

Untuk produksi, administrator server dapat menjadwalkan perintah pertama secara berkala. Penjadwalan belum dipasang oleh perubahan ini.

Order yang belum pernah dipilih metode pembayarannya bisa menghasilkan status 404 dari Core API. Itu **bukan bukti lunas dan bukan bukti aman membuat order kedua**. Jika pembuatan order tidak pasti atau status tetap tidak ditemukan, cocokkan order ID di dashboard/support Midtrans. Jangan menghapus order atau mengubah `active_bill_id` sebelum memastikan order tidak bisa dibayar. Refund perlu dicocokkan dengan laporan merchant dan pembukuan sekolah.

## Beralih ke produksi

Selesaikan verifikasi merchant, rekening pencairan, kanal VA, dan biaya transaksi terlebih dahulu. Gunakan Server Key serta Merchant ID **produksi**, `MIDTRANS_PRODUCTION=true`, `APP_ENV=production`, `APP_DEBUG=false`, domain HTTPS sekolah, dan konfigurasi callback produksi. Sandbox diblokir ketika aplikasi berjalan sebagai production. Selesaikan order lama sebelum mengganti akun/lingkungan; jangan mencampurkan order sandbox dengan database siswa produksi.

Lakukan uji pembayaran produksi terbatas dan rekonsiliasi pencairan bersama bendahara sebelum membuka pembayaran ke semua siswa. Integrasi ini belum menjalani uji menyeluruh menggunakan akun Midtrans sekolah.

## Transfer manual dan tunai

Saat gateway belum aktif, siswa mendapat satu tombol untuk membuka rekening utama dan mengunggah bukti. Nominal dihitung otomatis. Konfirmasi siswa selalu **pending**, termasuk daftar ulang; siswa tidak dapat menandai pembayaran tunai sebagai lunas sendiri. Tunai dicatat bendahara.

Bukti baru disimpan pada penyimpanan lokal privat dan hanya dibuka melalui endpoint petugas. Bukti lama yang sudah berada di disk publik tetap dapat dibaca demi kompatibilitas; pemindahan arsip lama belum dilakukan. Order VA aktif menghalangi pencatatan manual pada tagihan yang sama.

## Pemeriksaan otomatis

```shell
php vendor/bin/phpunit tests/Feature/PaymentCheckoutTest.php tests/Unit/PaymentSummaryTest.php
php artisan view:cache
npm run build
```

Tes memakai fixture dan respons HTTP tiruan, tanpa menghubungi Midtrans, mengirim WhatsApp, atau mengubah data pembayaran sekolah.
