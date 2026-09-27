# WhatsApp Business Cloud API

Aplikasi memakai **WhatsApp Business Platform Cloud API resmi dari Meta**, bukan Fonnte.

## Nilai yang perlu diisi di server hosting

Masuk ke Meta Business Manager → WhatsApp → API Setup, lalu isi nilai ini di `.env` hosting:

```env
WHATSAPP_PHONE_NUMBER_ID=...
WHATSAPP_ACCESS_TOKEN=...
WHATSAPP_WEBHOOK_VERIFY_TOKEN=buat-token-random-sendiri
WHATSAPP_SENDER_NUMBER=6281247075160
```

`WHATSAPP_PHONE_NUMBER_ID` bukan nomor telepon. Ambil nilainya dari panel **API Setup** Meta. Gunakan access token permanen dari System User, jangan token sementara dari halaman uji coba.

## Webhook

Daftarkan URL berikut di Meta:

```text
https://domain-sekolah-anda/api/webhooks/whatsapp
```

Gunakan nilai `WHATSAPP_WEBHOOK_VERIFY_TOKEN` yang sama ketika Meta meminta Verify Token. Pilih subscription minimal `messages` dan `message_template_status_update`.

## Template pesan

Pesan yang dimulai oleh sekolah di luar jendela percakapan 24 jam harus menggunakan template yang berstatus **Approved** di Meta. Buat template aktivasi dan reset kata sandi, kemudian masukkan namanya ke `WHATSAPP_TEMPLATE_ACTIVATION` dan `WHATSAPP_TEMPLATE_PASSWORD_RESET`.

Pesan balasan selama percakapan aktif 24 jam dikirim sebagai pesan teks biasa oleh aplikasi.
