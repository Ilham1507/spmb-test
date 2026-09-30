<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#f1f7f7;color:#172033;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f1f7f7;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;background:#ffffff;border:1px solid #d6e7e5;border-radius:20px;overflow:hidden;">
                    <tr>
                        <td style="background:#087d76;padding:28px 32px;color:#ffffff;">
                            <div style="font-size:12px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:#bdf3ed;">SPMB ONLINE</div>
                            <div style="margin-top:7px;font-size:23px;font-weight:700;line-height:1.25;">SMK Muhammadiyah 4 Cileungsi</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h1 style="margin:0 0 16px;font-size:25px;line-height:1.3;color:#172033;">Verifikasi alamat email Anda</h1>
                            <p style="margin:0 0 18px;font-size:16px;line-height:1.65;color:#455468;">Yth. {{ $name }},</p>
                            <p style="margin:0 0 18px;font-size:16px;line-height:1.65;color:#455468;">Terima kasih telah melengkapi data kontak pendaftaran. Klik tombol di bawah untuk mengonfirmasi bahwa alamat email ini aktif dan dapat digunakan untuk menerima informasi SPMB.</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:22px 0;background:#f4fbfa;border:1px solid #cdebe7;border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <div style="font-size:12px;font-weight:700;letter-spacing:.8px;color:#087d76;text-transform:uppercase;">Nomor pendaftaran</div>
                                        <div style="margin-top:6px;font-size:18px;font-weight:700;color:#172033;">{{ $registrationNumber }}</div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:28px 0;">
                                <tr>
                                    <td bgcolor="#087d76" style="border-radius:10px;">
                                        <a href="{{ $verificationUrl }}" style="display:inline-block;padding:14px 22px;color:#ffffff;text-decoration:none;font-size:16px;font-weight:700;">Verifikasi email saya</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:#66758a;">Tautan ini berlaku selama <strong>30 menit</strong>. Jika tombol tidak terbuka, salin dan buka tautan berikut di browser:</p>
                            <p style="margin:0;word-break:break-all;font-size:13px;line-height:1.55;color:#087d76;">{{ $verificationUrl }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px;border-top:1px solid #e4eeee;background:#fbfdfd;">
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#66758a;">Abaikan email ini bila Anda tidak merasa mendaftarkan diri. Tautan verifikasi hanya digunakan untuk mengaktifkan tahap berikutnya pada formulir pendaftaran.</p>
                            <p style="margin:13px 0 0;font-size:13px;color:#66758a;">Salam,<br><strong style="color:#314054;">Panitia SPMB SMK Muhammadiyah 4 Cileungsi</strong></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
