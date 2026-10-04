<?php

namespace Tests\Unit;

use App\Models\JenisTagihan;
use App\Models\Pendaftar;
use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use App\Models\User;
use App\Support\PaymentProof;
use App\Support\PaymentSummary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentProofTest extends TestCase
{
    private function transaction(string $type): TransaksiPembayaran
    {
        $student = new Pendaftar(['registration_number' => 'SPMB2028-UJI']);
        $student->setRelation('biodata', null);
        $student->setRelation('user', new User(['name' => 'SISWA UJI - BUKAN TRANSAKSI NYATA']));
        $bill = new TagihanPendaftar([
            'status' => 'partial', 'total_amount' => 350000, 'paid_amount' => 100000,
            'remaining_amount' => 250000, 'rincian_biaya' => [
                ['name' => 'Seragam', 'category' => 'Seragam & Perlengkapan', 'amount' => 200000],
                ['name' => 'Buku', 'category' => 'Buku & Pembelajaran', 'amount' => 50000],
                ['name' => 'SPP', 'category' => 'Biaya Pendidikan', 'amount' => 100000],
            ],
        ]);
        $bill->setRelation('jenisTagihan', new JenisTagihan(['name' => $type]));
        $bill->setRelation('pendaftar', $student);
        $transaction = new TransaksiPembayaran([
            'id' => 987, 'status' => 'verified', 'amount' => 100000,
            'payment_date' => '2026-10-02', 'payment_method' => 'transfer',
            'reference_number' => 'TRANSAKSI-UJI',
            'selected_items' => [['name' => 'Seragam', 'amount' => 100000]],
        ]);
        $transaction->setRelation('tagihan', $bill);
        $transaction->forceFill(['id' => 987]);
        $transaction->setRelation('checkout', null);
        $transaction->setRelation('verifier', new User(['name' => 'Panitia Uji']));
        $transaction->setRelation('treasurerReceiver', null);
        $bill->setRelation('transaksi', new Collection([$transaction]));

        return $transaction;
    }

    public function test_filenames_distinguish_payment_types(): void
    {
        $this->assertSame('Bukti Pembayaran Daftar Ulang - SPMB2028-UJI.pdf', PaymentProof::filename($this->transaction('Daftar ulang')));
        $this->assertSame('Bukti Pembayaran Formulir - SPMB2028-UJI.pdf', PaymentProof::filename($this->transaction('Uang Formulir Pendaftaran')));
        $this->assertTrue(PaymentProof::isReRegistration($this->transaction('Biaya DU')));
    }

    public function test_partial_payment_keeps_unpaid_components_and_remaining_amounts(): void
    {
        $summary = PaymentSummary::forBill($this->transaction('Daftar ulang')->tagihan);
        $this->assertEquals(100000, $summary['items'][0]['settled']);
        $this->assertEquals(100000, $summary['items'][0]['remaining']);
        $this->assertSame('Dibayar sebagian', $summary['items'][0]['status']);
        $this->assertEquals(50000, $summary['items'][1]['remaining']);
        $this->assertEquals(250000, $summary['items']->sum('remaining'));
    }

    public function test_both_receipts_render_as_pdf_with_all_fee_groups(): void
    {
        Schema::create('system_settings', function ($table) {
            $table->string('key');
            $table->text('value')->nullable();
        });
        foreach (['Daftar ulang', 'Uang Formulir Pendaftaran', 'BMT'] as $type) {
            $transaction = $this->transaction($type === 'BMT' ? 'Daftar ulang' : $type);
            if ($type === 'BMT') {
                $transaction->forceFill(['treasurer_received_at' => '2026-10-04 09:30:00', 'payment_method' => 'cash']);
                $transaction->setRelation('treasurerReceiver', new User(['name' => 'Bendahara Uji']));
                $transaction->setRelation('verifier', new User(['name' => 'Bendahara Uji']));
                // Full-size sample based on the fee names supplied by the user.
                // This is not a production student's bill or current price list.
                $fees = [
                    'FORMULIR' => 150000, 'INFAQ GEDUNG' => 825000, 'ZIS' => 150000,
                    'TABUNGAN' => 40000, 'FORTASI' => 70000, 'PEMBINAAN' => 150000,
                    'BUKU WAJIB' => 222000, 'BUKU RAPORT/VSKIL, PASPORT/VKHSU/VKSRS' => 120000,
                    'KARTU PELAJAR, PHOTO' => 120000, 'PAKAIAN OLAH RAGA' => 170000,
                    'SERAGAM HW' => 269000, 'BAJU BATIK' => 147000, 'BAJU ALMAMATER SMK 4' => 252000,
                    'Baju Jurusan SMK 4' => 230000, 'SPP' => 250000, 'IPM' => 120000,
                    'SIMULASI DIGITAL' => 192000, 'UKS' => 120000, 'DANA TA\'AWIN' => 35000,
                    'PRAKTEK (KEGIATAN JURUSAN)' => 650000, 'MAJALAH SEKOLAH' => 50000,
                    'GO SISWA' => 80000, 'UJIAN CBT' => 20000, 'CAMBRIDGE ENGLISH PROGRAM' => 150000,
                    'BAHASA JEPANG' => 200000, 'Buku Bahasa Jepang' => 194250,
                ];
                $items = collect($fees)->map(fn ($amount, $name) => ['name' => $name, 'amount' => $amount])->values()->all();
                $transaction->forceFill(['amount' => 350000, 'selected_items' => [['name' => 'INFAQ GEDUNG', 'amount' => 100000], ['name' => 'SPP', 'amount' => 250000]]]);
                $transaction->tagihan->forceFill(['rincian_biaya' => $items, 'total_amount' => array_sum($fees), 'paid_amount' => 350000, 'remaining_amount' => array_sum($fees) - 350000]);
            }
            $html = view('payments.system-proof', ['transaction' => $transaction, 'bmtProof' => $type === 'BMT'])->render();
            $this->assertStringContainsString('position: fixed; bottom: -10mm;', $html);
            $this->assertStringContainsString('margin: 8mm 8mm 18mm;', $html);
            $this->assertStringContainsString('text-align: center; line-height: 1.4;', $html);
            $this->assertStringContainsString('Kampus E - Jl. Akses Bojong Kaso', $html);
            $this->assertStringNotContainsString('Dokumen ini diterbitkan otomatis', $html);
            $this->assertStringNotContainsString('Status rincian adalah akumulasi', $html);
            $this->assertStringContainsString(pathinfo(PaymentProof::filename($transaction), PATHINFO_FILENAME), $html);
            if ($type !== 'Uang Formulir Pendaftaran') {
                $this->assertStringContainsString('Rincian biaya', $html);
                $this->assertStringContainsString($type === 'BMT' ? '<td>BUKU WAJIB</td>' : 'Buku &amp; Pembelajaran', $html);
                $this->assertStringContainsString('Belum lunas (sebagian)', $html);
                $this->assertStringContainsString($type === 'BMT' ? '200.000' : '250.000', $html);
                $this->assertStringNotContainsString('KELOMPOK BIAYA', $html);
                if ($type !== 'BMT') {
                    $this->assertStringNotContainsString('<td>Seragam</td>', $html);
                    $this->assertStringNotContainsString('<td>Buku</td>', $html);
                    $this->assertStringNotContainsString('<td>SPP</td>', $html);
                }
            }
            if ($type === 'BMT') {
                $this->assertStringContainsString('DITERIMA BMT', $html);
                $this->assertStringContainsString('Diterima oleh Bendahara Uji', $html);
                $this->assertStringContainsString('04 Oktober 2026, 09:30', $html);
                foreach ($transaction->tagihan->rincian_biaya as $item) {
                    $this->assertTrue(str_contains($html, '<td>'.e($item['name']).'</td>'), 'Missing receipt item: '.$item['name']);
                }
                $this->assertStringContainsString('<td>SPP</td>', $html);
                $this->assertStringContainsString('<td>Lunas</td>', $html);
                $this->assertStringNotContainsString('<td>Seragam &amp; Perlengkapan</td>', $html);
                $this->assertStringContainsString('BUKTI PEMBAYARAN BTM ANNISA', $html);
                $this->assertStringContainsString('class="fee-columns"', $html);
                $this->assertSame(2, substr_count($html, '<th class="fee-name">Rincian biaya</th>'));
                $this->assertStringNotContainsString('INFORMASI LANJUTAN', $html);
                $this->assertStringNotContainsString('Dokumen ini diterbitkan otomatis', $html);
                $this->assertStringNotContainsString('Status rincian adalah akumulasi', $html);
                $parentHtml = view('payments.system-proof', ['transaction' => $transaction])->render();
                $this->assertStringContainsString('class="group"', $parentHtml);
                $this->assertStringNotContainsString('<td>INFAQ GEDUNG</td>', $parentHtml);
            }
            $pdf = Pdf::loadHTML($html)->setPaper('a4')->output();
            $this->assertStringStartsWith('%PDF-', $pdf);
            if ($directory = getenv('PAYMENT_PROOF_PREVIEW_DIR')) {
                $filename = $type === 'BMT' ? 'Bukti Pembayaran BTM ANNISA - SPMB2028-UJI.pdf' : PaymentProof::filename($transaction);
                file_put_contents($directory.'/'.$filename, $pdf);
            }
        }
    }
}
