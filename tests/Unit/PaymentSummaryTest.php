<?php

namespace Tests\Unit;

use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use App\Support\PaymentSummary;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PaymentSummaryTest extends TestCase
{
    private function bill(array $transactions, string $status = 'partial'): TagihanPendaftar
    {
        $bill = new TagihanPendaftar(['status' => $status, 'rincian_biaya' => [
            ['name' => 'Seragam', 'amount' => 200000],
            ['name' => 'SPP Juli', 'amount' => 100000],
            ['name' => 'Buku', 'amount' => 50000],
        ]]);
        $bill->setRelation('transaksi', new Collection(array_map(fn ($attributes) => (new TransaksiPembayaran)->forceFill($attributes), $transactions)));

        return $bill;
    }

    public function test_pending_and_rejected_payments_do_not_mark_items_as_paid(): void
    {
        $summary = PaymentSummary::forBill($this->bill([
            ['id' => 1, 'status' => 'verified', 'payment_date' => '2026-09-01', 'amount' => 200000, 'selected_items' => [['name' => 'Seragam', 'amount' => 200000]]],
            ['id' => 2, 'status' => 'pending', 'payment_date' => '2026-09-03', 'amount' => 100000, 'selected_items' => [['name' => 'SPP Juli', 'amount' => 100000]]],
            ['id' => 3, 'status' => 'rejected', 'payment_date' => '2026-09-04', 'amount' => 50000, 'selected_items' => [['name' => 'Buku', 'amount' => 50000]]],
        ]));
        $this->assertSame(['Lunas', 'Menunggu dicek', 'Belum dibayar'], $summary['items']->pluck('status')->all());
        $this->assertEquals(100000, $summary['pending']);
        $this->assertSame(1, $summary['last']->id);
    }

    public function test_legacy_partial_payment_does_not_guess_which_item_was_paid(): void
    {
        $summary = PaymentSummary::forBill($this->bill([
            ['status' => 'verified', 'amount' => 100000],
        ]));
        $this->assertTrue($summary['unallocated']);
        $this->assertSame(['Perlu dicocokkan'], $summary['items']->pluck('status')->unique()->values()->all());
    }

    public function test_fully_paid_legacy_bill_marks_all_items_paid(): void
    {
        $summary = PaymentSummary::forBill($this->bill([], 'paid'));
        $this->assertSame(['Lunas'], $summary['items']->pluck('status')->unique()->values()->all());
    }

    public function test_shared_summary_renders_amounts_and_escapes_fee_names(): void
    {
        $bill = $this->bill([]);
        $bill->forceFill(['total_amount' => 350000, 'paid_amount' => 0, 'remaining_amount' => 350000,
            'rincian_biaya' => [['name' => '<script>alert(1)</script>', 'amount' => 350000]],
        ]);
        $this->blade('<x-payment-summary :bill="$bill" />', ['bill' => $bill])
            ->assertSee('Sisa tagihan')
            ->assertSee('350.000')
            ->assertSee('Belum ada pembayaran yang diterima.')
            ->assertDontSee('<script>', false);
    }

    public function test_partial_item_is_not_marked_paid_and_last_payment_is_sorted(): void
    {
        $summary = PaymentSummary::forBill($this->bill([
            ['id' => 2, 'status' => 'verified', 'payment_date' => '2026-09-02', 'selected_items' => [['name' => 'Seragam', 'amount' => 50000]]],
            ['id' => 1, 'status' => 'verified', 'payment_date' => '2026-09-01', 'selected_items' => [['name' => 'Seragam', 'amount' => 50000]]],
        ]));
        $this->assertSame('Dibayar sebagian', $summary['items']->first()['status']);
        $this->assertSame(2, $summary['last']->id);
    }

    public function test_student_payment_page_asks_for_a_fee_selection_before_checkout(): void
    {
        \Illuminate\Support\Facades\Schema::create('system_settings', function ($table) {
            $table->string('key');
            $table->text('value')->nullable();
        });
        $this->withoutVite();
        config(['payments.midtrans.enabled' => true, 'payments.midtrans.server_key' => 'test-server-key', 'payments.midtrans.merchant_id' => 'TEST']);
        $user = new \App\Models\User(['name' => 'Siswa Uji']);
        $user->setRelation('pendaftar', null);
        $this->actingAs($user);
        $bill = $this->bill([]);
        $bill->forceFill(['id' => 10, 'total_amount' => 350000, 'remaining_amount' => 250000, 'paid_amount' => 100000]);
        $bill->setRelation('jenisTagihan', new \App\Models\JenisTagihan(['name' => 'Daftar ulang']));
        $this->view('peserta.pembayaran.index', [
            'pendaftar' => new \App\Models\Pendaftar(['registration_status' => 'accepted']),
            'tagihans' => collect([$bill]), 'registrationFeePaid' => true, 'registrationFeePending' => false,
            'rekeningAktif' => collect(), 'gatewayReady' => true, 'activeCheckouts' => collect(),
        ])->assertSee('Bayar daftar ulang')->assertSee('selected_items[]', false)->assertSee('Rincian', false);
    }
}
