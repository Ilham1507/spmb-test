<?php

namespace Tests\Unit;

use App\Models\InvoiceEmailDelivery;
use App\Models\KontakPendaftar;
use App\Models\Pendaftar;
use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use App\Services\InvoiceEmailNotifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InvoiceEmailNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array']);
        (require base_path('database/migrations/2026_10_01_150000_create_invoice_email_deliveries_table.php'))->up();
    }

    public function test_only_confirmed_payments_to_verified_email_are_sent_once(): void
    {
        $notifier = $this->notifier();
        $transaction = $this->payment();
        $transaction->status = 'pending';
        $this->assertFalse($notifier->send($transaction));
        $transaction->status = 'verified';
        $transaction->tagihan->pendaftar->kontak->email_verified_at = null;
        $this->assertFalse($notifier->send($transaction));
        $transaction->tagihan->pendaftar->kontak->email_verified_at = now();
        $this->assertTrue($notifier->send($transaction));
        $this->assertFalse($notifier->send($transaction));
        $this->assertSame(1, $notifier->calls);
        $this->assertNotNull(InvoiceEmailDelivery::first()->sent_at);
    }

    public function test_failed_delivery_can_be_retried_and_new_verified_address_gets_its_own_copy(): void
    {
        $notifier = $this->notifier();
        $transaction = $this->payment();
        $notifier->fail = true;
        $this->assertFalse($notifier->send($transaction));
        $this->assertNull(InvoiceEmailDelivery::first()->sent_at);
        $notifier->fail = false;
        $this->assertTrue($notifier->send($transaction));
        $transaction->tagihan->pendaftar->kontak->email = 'new@example.com';
        $this->assertTrue($notifier->send($transaction));
        $this->assertSame(2, InvoiceEmailDelivery::whereNotNull('sent_at')->count());
    }

    public function test_verification_catchup_selects_only_this_students_confirmed_payments(): void
    {
        Schema::create('tagihan_pendaftar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
        });
        Schema::create('transaksi_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bill_id');
            $table->string('status');
        });
        DB::table('tagihan_pendaftar')->insert([['id' => 1, 'applicant_id' => 17], ['id' => 2, 'applicant_id' => 99]]);
        DB::table('transaksi_pembayaran')->insert([
            ['id' => 1, 'bill_id' => 1, 'status' => 'verified'],
            ['id' => 2, 'bill_id' => 1, 'status' => 'pending'],
            ['id' => 3, 'bill_id' => 1, 'status' => 'rejected'],
            ['id' => 4, 'bill_id' => 2, 'status' => 'verified'],
        ]);
        $notifier = new class extends InvoiceEmailNotifier
        {
            public array $sent = [];

            public function send(TransaksiPembayaran $transaction): bool
            {
                $this->sent[] = $transaction->id;

                return true;
            }
        };
        $contact = new KontakPendaftar(['applicant_id' => 17, 'email' => 'student@example.com']);
        $notifier->sendVerifiedForContact($contact);
        $this->assertSame([], $notifier->sent);
        $contact->email_verified_at = now();
        $notifier->sendVerifiedForContact($contact);
        $this->assertSame([1], $notifier->sent);
    }

    private function notifier(): InvoiceEmailNotifier
    {
        return new class extends InvoiceEmailNotifier
        {
            public int $calls = 0;

            public bool $fail = false;

            protected function deliver(TransaksiPembayaran $transaction): void
            {
                $this->calls++;
                if ($this->fail) {
                    throw new \RuntimeException('Temporary mail failure');
                }
            }
        };
    }

    private function payment(): TransaksiPembayaran
    {
        $contact = new KontakPendaftar(['email' => 'student@example.com', 'email_verified_at' => now()]);
        $applicant = (new Pendaftar)->setRelation('kontak', $contact)->setRelation('biodata', null)->setRelation('user', null);
        $bill = (new TagihanPendaftar)->setRelation('pendaftar', $applicant)->setRelation('jenisTagihan', null);
        $transaction = new TransaksiPembayaran(['status' => 'verified']);
        $transaction->id = 123;

        return $transaction->setRelation('tagihan', $bill)->setRelation('verifier', null)->setRelation('treasurerReceiver', null);
    }
}
