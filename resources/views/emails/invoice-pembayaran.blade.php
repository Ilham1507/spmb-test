<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:32px 16px;background:#f1f7f7;color:#172033;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;overflow:hidden;border:1px solid #d6e7e5;border-radius:20px;background:#fff;">
            <tr><td style="padding:26px 30px;background:#087d76;color:#fff;"><div style="font-size:12px;font-weight:700;letter-spacing:1.4px;">SPMB ONLINE</div><div style="margin-top:7px;font-size:22px;font-weight:700;">SMK Muhammadiyah 4 Cileungsi</div></td></tr>
            <tr><td style="padding:30px;"><h1 style="margin:0 0 16px;font-size:23px;color:#172033;">Invoice pembayaran</h1><p style="margin:0 0 14px;font-size:16px;line-height:1.6;color:#455468;">Yth. {{ $student }}, pembayaran Anda telah disetujui. Invoice PDF terlampir pada email ini.</p><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4fbfa;border:1px solid #cdebe7;border-radius:12px;"><tr><td style="padding:16px 18px;font-size:14px;line-height:1.75;color:#455468;"><strong style="color:#172033;">No. pendaftaran:</strong> {{ $registrationNumber }}<br><strong style="color:#172033;">Jenis pembayaran:</strong> {{ $feeName }}<br><strong style="color:#172033;">Nominal:</strong> Rp {{ $amount }}</td></tr></table><p style="margin:20px 0 0;font-size:14px;line-height:1.6;color:#66758a;">Simpan invoice ini sebagai bukti pembayaran. Status pendaftaran juga dapat dipantau melalui WhatsApp dan portal SPMB.</p></td></tr>
        </table>
    </td></tr></table>
</body>
</html>
