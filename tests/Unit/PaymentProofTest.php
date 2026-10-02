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
        foreach (['Daftar ulang', 'Uang Formulir Pendaftaran'] as $type) {
            $transaction = $this->transaction($type);
            $html = view('payments.system-proof', compact('transaction'))->render();
            $this->assertStringContainsString(pathinfo(PaymentProof::filename($transaction), PATHINFO_FILENAME), $html);
            if ($type === 'Daftar ulang') {
                $this->assertStringContainsString('Rincian biaya', $html);
                $this->assertStringContainsString('Buku &amp; Pembelajaran', $html);
                $this->assertStringContainsString('Belum lunas (sebagian)', $html);
                $this->assertStringContainsString('250.000', $html);
                $this->assertStringNotContainsString('KELOMPOK BIAYA', $html);
            }
            $pdf = Pdf::loadHTML($html)->setPaper('a4')->output();
            $this->assertStringStartsWith('%PDF-', $pdf);
            if ($directory = getenv('PAYMENT_PROOF_PREVIEW_DIR')) {
                file_put_contents($directory.'/'.PaymentProof::filename($transaction), $pdf);
            }
        }
    }
}
