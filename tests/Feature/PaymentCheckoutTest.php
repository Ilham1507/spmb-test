<?php

namespace Tests\Feature;

use App\Models\{JenisTagihan, PaymentCheckout, Pendaftar, Peran, RekeningSekolah, TagihanPendaftar, TransaksiPembayaran, User};
use App\Services\{MidtransClient, PaymentCheckoutService};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Schema, Storage};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentCheckoutTest extends TestCase
{
    private TagihanPendaftar $bill;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['payments.midtrans.enabled' => true, 'payments.midtrans.production' => false,
            'payments.midtrans.server_key' => 'test-server-key', 'payments.midtrans.merchant_id' => 'TEST-MERCHANT']);
        Schema::create('system_settings', function (Blueprint $t) { $t->string('key'); $t->text('value')->nullable(); });
        Schema::create('jenis_tagihan', function (Blueprint $t) { $t->id(); $t->string('name'); $t->decimal('default_amount', 14, 2)->default(100000); $t->timestamps(); });
        Schema::create('pendaftar', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('user_id'); $t->string('registration_status'); $t->string('registration_number')->nullable(); $t->timestamps(); });
        Schema::create('tagihan_pendaftar', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('applicant_id'); $t->unsignedBigInteger('bill_type_id');
            $t->decimal('total_amount', 14, 2); $t->decimal('paid_amount', 14, 2)->default(0);
            $t->decimal('remaining_amount', 14, 2); $t->string('status'); $t->json('rincian_biaya')->nullable(); $t->timestamps();
        });
        Schema::create('transaksi_pembayaran', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('bill_id'); $t->string('transaction_number')->unique();
            $t->string('reference_number')->nullable(); $t->timestamp('payment_date'); $t->decimal('amount', 14, 2);
            $t->json('selected_items')->nullable(); $t->string('payment_method'); $t->string('status');
            $t->string('proof_file')->nullable(); $t->text('notes')->nullable();
            $t->unsignedBigInteger('verified_by')->nullable(); $t->timestamp('verified_at')->nullable(); $t->timestamps();
        });
        Schema::create('rekening_sekolah', function (Blueprint $t) { $t->id(); $t->boolean('status'); $t->timestamps(); });
        (require database_path('migrations/2026_09_05_160000_create_payment_checkouts_table.php'))->up();
        $applicant = Pendaftar::create(['user_id' => 1, 'registration_status' => 'accepted', 'registration_number' => 'SPMB2026-0001']);
        $type = JenisTagihan::create(['name' => 'Formulir pendaftaran']);
        $this->bill = TagihanPendaftar::create(['applicant_id' => $applicant->id, 'bill_type_id' => $type->id,
            'total_amount' => 100000, 'remaining_amount' => 100000, 'status' => 'unpaid']);
        $this->student = (new User)->forceFill(['id' => 1, 'name' => 'Siswa Uji']);
        $this->student->setRelation('pendaftar', $applicant)->setRelation('role', new Peran(['name' => 'peserta']));
        $this->actingAs($this->student);
    }

    private function start(): PaymentCheckout
    {
        Http::fake(['*/snap/v1/transactions' => Http::response(['redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/test-token'])]);
        return app(PaymentCheckoutService::class)->start($this->bill, 100000);
    }

    private function providerStatus(PaymentCheckout $checkout, array $overrides = []): array
    {
        return array_merge(['order_id' => $checkout->order_id, 'gross_amount' => '100000.00',
            'merchant_id' => 'TEST-MERCHANT', 'currency' => 'IDR', 'status_code' => '200',
            'transaction_id' => 'provider-trx-1', 'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer', 'fraud_status' => 'accept'], $overrides);
    }

    private function mockStatus(array $payload): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(['*/status' => Http::response($payload)]);
    }

    private function webhook(array $payload)
    {
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'test-server-key');
        return $this->postJson('/api/webhooks/midtrans', $payload);
    }

    public function test_checkout_uses_server_amount_and_reuses_order_on_double_click(): void
    {
        $first = $this->start();
        $second = app(PaymentCheckoutService::class)->start($this->bill, 1);
        $this->assertSame($first->id, $second->id);
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r['transaction_details']['gross_amount'] === 100000 && $r['enabled_payments'] === config('payments.midtrans.enabled_payments'));
        $this->assertSame('unpaid', $this->bill->fresh()->status);
    }

    public function test_modified_amount_is_rejected_before_contacting_provider(): void
    {
        $this->post(route('peserta.pembayaran.checkout', $this->bill), ['expected_amount' => 1])->assertRedirect()->assertSessionHasErrors('payment');
        Http::assertNothingSent();
        $this->assertDatabaseCount('payment_checkouts', 0);
    }

    public function test_another_students_bill_is_forbidden(): void
    {
        $this->bill->update(['applicant_id' => 999]);
        $this->postJson(route('peserta.pembayaran.checkout', $this->bill), ['expected_amount' => 100000])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_another_students_checkout_cannot_be_refreshed(): void
    {
        $checkout = $this->start();
        $this->bill->update(['applicant_id' => 999]);
        $this->postJson(route('peserta.pembayaran.refresh', $checkout))->assertForbidden();
        Http::assertSentCount(1);
    }

    public function test_formulir_va_settlement_waits_for_staff_approval_exactly_once(): void
    {
        $checkout = $this->start();
        $status = $this->providerStatus($checkout);
        $this->mockStatus($status);
        $this->webhook($status)->assertOk();
        $this->webhook($status)->assertOk();
        $this->assertSame('unpaid', $this->bill->fresh()->status);
        $this->assertEquals(0, $this->bill->fresh()->paid_amount);
        $this->assertDatabaseHas('transaksi_pembayaran', ['status' => 'pending', 'amount' => 100000]);
        $this->assertNull($checkout->fresh()->active_bill_id);
        $this->assertSame('awaiting_approval', $checkout->fresh()->status);
    }

    public function test_invalid_signature_is_rejected_without_status_lookup(): void
    {
        $checkout = $this->start();
        $this->postJson('/api/webhooks/midtrans', $this->providerStatus($checkout) + ['signature_key' => 'forged'])->assertForbidden();
        Http::assertSentCount(1);
        $this->assertDatabaseCount('transaksi_pembayaran', 0);
    }

    public function test_callback_status_and_return_query_cannot_fake_payment(): void
    {
        $checkout = $this->start();
        $this->mockStatus($this->providerStatus($checkout, ['transaction_status' => 'pending']));
        $this->webhook($this->providerStatus($checkout))->assertOk();
        $this->assertSame('unpaid', $this->bill->fresh()->status);
        $this->assertDatabaseCount('transaksi_pembayaran', 0);
    }

    public function test_wrong_amount_currency_or_merchant_never_settles(): void
    {
        $checkout = $this->start();
        foreach ([['gross_amount' => '99999.00'], ['currency' => 'USD'], ['merchant_id' => 'OTHER'], ['order_id' => 'OTHER']] as $invalid) {
            $this->mockStatus($this->providerStatus($checkout, $invalid));
            $this->webhook($this->providerStatus($checkout))->assertStatus(503);
        }
        $this->assertDatabaseCount('transaksi_pembayaran', 0);
    }

    public function test_expired_order_releases_bill_without_credit(): void
    {
        $checkout = $this->start();
        $this->mockStatus($this->providerStatus($checkout, ['transaction_status' => 'expire']));
        $this->webhook($this->providerStatus($checkout))->assertOk();
        $this->assertNull($checkout->fresh()->active_bill_id);
        $this->assertSame('closed', $checkout->fresh()->status);
        $this->assertDatabaseCount('transaksi_pembayaran', 0);
    }

    public function test_missing_status_does_not_mean_paid_or_safe_to_duplicate(): void
    {
        $checkout = $this->start();
        $this->mockStatus(['status_code' => '404']);
        app(PaymentCheckoutService::class)->refresh($checkout);
        $this->assertNotNull($checkout->fresh()->active_bill_id);
        $this->assertSame('pending', $checkout->fresh()->status);
        $this->assertDatabaseCount('transaksi_pembayaran', 0);
    }

    public function test_uncertain_creation_retains_order_and_prevents_a_second_post(): void
    {
        Http::fake(['*/snap/v1/transactions' => Http::failedConnection()]);
        try {
            app(PaymentCheckoutService::class)->start($this->bill, 100000);
            $this->fail('Expected validation error');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
        $checkout = app(PaymentCheckoutService::class)->start($this->bill, 100000);
        $this->assertSame('needs_review', $checkout->status);
        $this->assertDatabaseCount('payment_checkouts', 1);
        $this->assertNotNull($checkout->active_bill_id);
    }

    public function test_provider_cannot_redirect_to_an_unrelated_site(): void
    {
        $this->expectException(\RuntimeException::class);
        app(MidtransClient::class)->assertRedirect('https://app.sandbox.midtrans.com.evil.example/snap/test', false);
    }

    public function test_changed_bill_is_flagged_instead_of_overcredited(): void
    {
        $checkout = $this->start();
        $this->bill->update(['paid_amount' => 50000, 'remaining_amount' => 50000]);
        $this->mockStatus($this->providerStatus($checkout));
        $this->webhook($this->providerStatus($checkout))->assertOk();
        $this->assertSame('needs_review', $checkout->fresh()->status);
        $this->assertDatabaseCount('transaksi_pembayaran', 0);
    }

    public function test_refund_is_flagged_without_double_credit_or_silent_reversal(): void
    {
        $checkout = $this->start();
        $this->mockStatus($this->providerStatus($checkout));
        $this->webhook($this->providerStatus($checkout))->assertOk();
        $this->mockStatus($this->providerStatus($checkout, ['transaction_status' => 'refund']));
        $this->webhook($this->providerStatus($checkout))->assertOk();
        $this->assertSame('needs_review', $checkout->fresh()->status);
        $this->assertDatabaseCount('transaksi_pembayaran', 1);
    }

    public function test_disabled_gateway_makes_no_request(): void
    {
        config(['payments.midtrans.enabled' => false]);
        $this->post(route('peserta.pembayaran.checkout', $this->bill), ['expected_amount' => 100000])->assertRedirect()->assertSessionHasErrors('payment');
        Http::assertNothingSent();
    }

    public function test_student_cash_proof_waits_for_staff_approval(): void
    {
        Storage::fake('local');
        $this->post(route('peserta.pembayaran.store', $this->bill), [
            'amount' => 100000, 'payment_method' => 'cash',
            'proof_file' => UploadedFile::fake()->image('bukti-cash.jpg'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('transaksi_pembayaran', ['status' => 'pending', 'payment_method' => 'cash', 'amount' => 100000]);
    }

    public function test_manual_reregistration_is_pending(): void
    {
        Storage::fake('local');
        RekeningSekolah::create(['status' => 1]);
        $this->bill->update(['status' => 'paid', 'paid_amount' => 100000, 'remaining_amount' => 0]);
        $type = JenisTagihan::create(['name' => 'Daftar ulang']);
        $bill = TagihanPendaftar::create(['applicant_id' => $this->student->pendaftar->id, 'bill_type_id' => $type->id,
            'total_amount' => 100000, 'remaining_amount' => 100000, 'status' => 'unpaid']);
        $this->post(route('peserta.pembayaran.store', $bill), [
            'amount' => 100000, 'payment_method' => 'transfer',
            'proof_file' => UploadedFile::fake()->createWithContent('bukti.pdf', "%PDF-1.4\nTest document"),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('transaksi_pembayaran', ['bill_id' => $bill->id, 'status' => 'pending']);
        $this->assertSame('unpaid', $bill->fresh()->status);
        $this->assertEquals(0, $bill->fresh()->paid_amount);
    }

    public function test_student_can_cancel_a_pending_va_before_it_is_paid(): void
    {
        $checkout = $this->start();
        Http::fake(['*/cancel' => Http::response(['transaction_status' => 'cancel'])]);

        $this->post(route('peserta.pembayaran.cancel', $checkout))->assertRedirect(route('peserta.pembayaran'));

        $this->assertSame('closed', $checkout->fresh()->status);
        $this->assertNull($checkout->fresh()->active_bill_id);
        $this->assertSame('unpaid', $this->bill->fresh()->status);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/cancel') && $request->method() === 'POST');
    }

    public function test_pending_va_is_expired_when_provider_rejects_cancel(): void
    {
        $checkout = $this->start();
        Http::fake([
            '*/cancel' => Http::response(['status_message' => 'cannot cancel'], 400),
            '*/expire' => Http::response(['status_message' => 'expired']),
        ]);

        $this->post(route('peserta.pembayaran.cancel', $checkout))->assertRedirect(route('peserta.pembayaran'));

        $this->assertSame('closed', $checkout->fresh()->status);
        $this->assertNull($checkout->fresh()->active_bill_id);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/expire') && $request->method() === 'POST');
    }

    public function test_student_selects_the_cost_items_before_a_va_is_created(): void
    {
        $this->bill->update([
            'total_amount' => 300000,
            'remaining_amount' => 300000,
            'rincian_biaya' => [
                ['name' => 'Seragam', 'amount' => 200000],
                ['name' => 'Buku', 'amount' => 100000],
            ],
        ]);
        Http::fake(['*/snap/v1/transactions' => Http::response(['redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/test-token'])]);

        $this->post(route('peserta.pembayaran.checkout', $this->bill), [
            'expected_amount' => 100000,
            'selected_items' => ['Buku'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payment_checkouts', ['amount' => 100000]);
        $this->assertDatabaseHas('payment_checkouts', ['selected_items' => json_encode([['name' => 'Buku', 'amount' => 100000]])]);
    }

    public function test_formulir_component_must_be_paid_before_other_reregistration_costs(): void
    {
        $type = JenisTagihan::create(['name' => 'Daftar ulang']);
        $bill = TagihanPendaftar::create([
            'applicant_id' => $this->student->pendaftar->id, 'bill_type_id' => $type->id,
            'total_amount' => 1075000, 'remaining_amount' => 1075000, 'status' => 'unpaid',
            'rincian_biaya' => [
                ['name' => 'FORMULIR', 'amount' => 150000],
                ['name' => 'INFAQ GEDUNG', 'amount' => 825000],
                ['name' => 'ZIS', 'amount' => 100000],
            ],
        ]);

        $quote = \App\Support\PaymentQuote::forBill($bill);

        $this->assertTrue($quote['requires_selection']);
        $this->assertSame([['name' => 'FORMULIR', 'amount' => 150000]], $quote['items']);
    }

    public function test_reregistration_can_allocate_a_partial_payment_to_selected_costs(): void
    {
        $type = JenisTagihan::create(['name' => 'Daftar ulang']);
        $bill = TagihanPendaftar::create([
            'applicant_id' => $this->student->pendaftar->id, 'bill_type_id' => $type->id,
            'total_amount' => 300000, 'remaining_amount' => 300000, 'status' => 'unpaid',
            'rincian_biaya' => [
                ['name' => 'Seragam', 'amount' => 200000],
                ['name' => 'Buku', 'amount' => 100000],
            ],
        ]);

        $quote = \App\Support\PaymentQuote::forBill($bill, ['Seragam', 'Buku'], 150000);

        $this->assertTrue($quote['valid_selection']);
        $this->assertSame(150000, $quote['amount']);
        $this->assertSame([['name' => 'Seragam', 'amount' => 150000]], $quote['selected_items']);
    }

    public function test_cashier_can_record_a_partial_reregistration_payment_for_selected_costs(): void
    {
        $type = JenisTagihan::create(['name' => 'Daftar Ulang']);
        $bill = TagihanPendaftar::create([
            'applicant_id' => $this->bill->applicant_id,
            'bill_type_id' => $type->id,
            'total_amount' => 300000,
            'remaining_amount' => 300000,
            'status' => 'unpaid',
            'rincian_biaya' => [
                ['name' => 'Seragam', 'amount' => 200000],
                ['name' => 'Buku', 'amount' => 100000],
            ],
        ]);

        $this->withoutMiddleware()->post(route('bendahara.pembayaran.store'), [
            'bill_id' => $bill->id,
            'selected_items' => ['Seragam', 'Buku'],
            'amount' => 150000,
            'payment_method' => 'cash',
        ])->assertRedirect(route('bendahara.pembayaran.index'));

        $bill->refresh();
        $transaction = TransaksiPembayaran::where('bill_id', $bill->id)->sole();
        $this->assertSame(150000.0, (float) $bill->paid_amount);
        $this->assertSame(150000.0, (float) $bill->remaining_amount);
        $this->assertSame([['name' => 'Seragam', 'amount' => 150000]], $transaction->selected_items);
    }

    public function test_va_is_not_created_without_a_valid_cost_selection(): void
    {
        $this->bill->update([
            'total_amount' => 300000,
            'remaining_amount' => 300000,
            'rincian_biaya' => [
                ['name' => 'Seragam', 'amount' => 200000],
                ['name' => 'Buku', 'amount' => 100000],
            ],
        ]);

        $this->post(route('peserta.pembayaran.checkout', $this->bill), ['expected_amount' => 300000])
            ->assertRedirect()->assertSessionHasErrors('selected_items');
        Http::assertNothingSent();
        $this->assertDatabaseCount('payment_checkouts', 0);
    }

    public function test_open_va_blocks_manual_proof_and_removes_unused_upload(): void
    {
        $this->start();
        Storage::fake('local');
        RekeningSekolah::create(['status' => 1]);
        $this->post(route('peserta.pembayaran.store', $this->bill), [
            'amount' => 100000, 'payment_method' => 'transfer',
            'proof_file' => UploadedFile::fake()->createWithContent('bukti.pdf', "%PDF-1.4\nTest document"),
        ])->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('transaksi_pembayaran', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('bukti_pembayaran'));
    }

    public function test_pending_manual_payment_blocks_va(): void
    {
        TransaksiPembayaran::create(['bill_id' => $this->bill->id, 'transaction_number' => 'MAN-1',
            'amount' => 100000, 'payment_date' => now(), 'payment_method' => 'transfer', 'status' => 'pending']);
        $this->post(route('peserta.pembayaran.checkout', $this->bill), ['expected_amount' => 100000])
            ->assertSessionHasErrors('payment');
        Http::assertNothingSent();
    }

    public function test_discount_is_applied_only_when_payment_is_approved(): void
    {
        $transaction = TransaksiPembayaran::create(['bill_id' => $this->bill->id, 'transaction_number' => 'PROMO-1',
            'amount' => 80000, 'discount_amount' => 20000, 'bill_total_snapshot' => 100000,
            'payment_date' => now(), 'payment_method' => 'transfer', 'status' => 'pending']);
        $this->assertEquals(100000, $this->bill->fresh()->total_amount);
        \Illuminate\Support\Facades\DB::transaction(function () use ($transaction) {
            \App\Support\VerifiedPayment::apply(TagihanPendaftar::lockForUpdate()->find($this->bill->id), $transaction);
        });
        $this->assertEquals(80000, $this->bill->fresh()->total_amount);
        $this->assertEquals(80000, $this->bill->fresh()->paid_amount);
        $this->assertSame('paid', $this->bill->fresh()->status);
    }
}
