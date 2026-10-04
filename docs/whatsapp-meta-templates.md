# Template WhatsApp SPMB

Nomor sekolah memakai Meta Cloud API. Token dan App Secret hanya disimpan di server.

| Kejadian | Nama template | Parameter body berurutan |
| --- | --- | --- |
| Formulir baru | spmb_formulir_masuk_v2 | nama, nomor pendaftaran, tanggal tes Indonesia, penerima kunjungan |
| Transfer menunggu pemeriksaan | spmb_bukti_transfer_masuk_v2 | baru/ulang, nama, nomor pendaftaran, biaya, nominal, penerima kunjungan, tautan petugas |
| Pembayaran ke penerima kunjungan | spmb_penerimaan_petugas | nama, nomor pendaftaran, biaya, nominal, metode, penerima kunjungan, verifier, sisa |
| DU menunggu bendahara | spmb_du_penerimaan_bendahara | nama, nominal, approver, tautan bendahara |
| Jadwal berubah | spmb_perubahan_jadwal_tes | nama, nomor pendaftaran, jadwal lama, jadwal baru, keterangan |
| Invoice formulir (DOCUMENT) | spmb_invoice_formulir | nama, approver, kontak penerima kunjungan |
| Invoice DU (DOCUMENT) | spmb_invoice_daftar_ulang | nama, approver |
| Diterima bendahara (DOCUMENT) | spmb_invoice_diterima_bendahara | biaya, nama, nominal, bendahara, langsung diterima/status approval |
| Aktivasi/reset (AUTHENTICATION) | spmb_kode_verifikasi | OTP yang sama di body dan tombol Salin kode |

Template invoice mempertahankan teks yang dipakai controller: formulir berisi petunjuk melengkapi formulir dan kontak petugas; DU berisi ketentuan pembayaran lanjutan Selasa/Jumat 07.30–14.30; penerimaan bendahara tidak meminta approval ulang. Header PDF memakai URL signed dan nama file PaymentProof, bukan tautan teks saja.

Server memeriksa status APPROVED dan bahasa id melalui API Meta, dengan cache 60 detik. Template pending/rejected tidak dikirim. Jika belum approved, teks/PDF lama hanya boleh dikirim dalam percakapan aktif. Sesi lokal dibatasi 23 jam sebagai margin dari jendela 24 jam Meta. Tanpa sesi aktif, kegagalan ditampilkan/logged, bukan dianggap terkirim hanya karena HTTP API menerima permintaan.

OTP 6 digit disimpan hashed pada password_reset_tokens, berlaku 5 menit, digunakan satu kali dalam transaksi DB, dan dibatasi 5 percobaan gagal per nomor selama 15 menit. Halaman Buat/lupa sandi memiliki tautan Masukkan kode. Tidak ada password/OTP produksi yang disimpan di Git.

Pada 4 Oktober 2026, pembuatan template Authentication ditolak Meta dengan code 10/subcode 2388185; akun WhatsApp belum diizinkan membuat template tersebut. Ini bukan bukti bahwa template OTP sudah diajukan/disetujui. OTP free-form hanya bekerja setelah penerima mengirim pesan ke nomor sekolah. Jangan menganggap semua notifikasi produksi siap sebelum template approved dan delivery luar sesi diuji.
