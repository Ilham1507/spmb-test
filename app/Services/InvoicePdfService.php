<?php

namespace App\Services;

use App\Models\Payment;

class InvoicePdfService
{
    /** Minimal PDF tanpa paket eksternal; dapat dikirim sebagai dokumen oleh gateway WA. */
    public function make(Payment $payment): string
    {
        $payment->loadMissing('siswa', 'details', 'approver', 'treasurer');
        $lines = ['INVOICE BMT - SMK MUHAMMADIYAH 4 CILEUNGSI', 'No. '.$payment->invoice_number, 'Siswa: '.$payment->siswa->nama, 'Jenis: '.strtoupper($payment->payment_type), 'Nominal: Rp '.number_format($payment->amount, 0, ',', '.'), 'Disetujui panitia: '.($payment->approver?->name ?? 'Belum disetujui'), 'Diterima bendahara: '.($payment->treasurer?->name ?? 'Belum diterima')];
        foreach ($payment->details as $detail) $lines[] = $detail->fee_name.': Rp '.number_format($detail->amount, 0, ',', '.');
        $text = "BT\n/F1 13 Tf\n50 770 Td\n";
        foreach ($lines as $i => $line) $text .= ($i ? "0 -22 Td\n" : '').'('.$this->escape($line).") Tj\n";
        $objects = ["<< /Type /Catalog /Pages 2 0 R >>", "<< /Type /Pages /Kids [3 0 R] /Count 1 >>", "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>", "<< /Length ".strlen($text)." >>\nstream\n{$text}endstream", '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'];
        $pdf = "%PDF-1.4\n"; $offsets = [0];
        foreach ($objects as $n => $object) { $offsets[] = strlen($pdf); $pdf .= ($n + 1)." 0 obj\n{$object}\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n"; foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
        return $pdf."trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }
    private function escape(string $value): string { return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $value); }
}
