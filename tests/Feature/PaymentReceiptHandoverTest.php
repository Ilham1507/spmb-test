<?php

namespace Tests\Feature;

use App\Models\{Peran, TransaksiPembayaran, User};
use App\Services\{InvoiceEmailNotifier, PaymentReceiptNotifier, WhatsappCloudApiService};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Http, Schema};
use Tests\TestCase;

class PaymentReceiptHandoverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32))]);
        Schema::create('pengguna', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('phone'); $t->timestamps(); });
        Schema::create('pendaftar', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('user_id'); $t->string('registration_number'); $t->timestamps(); });
        Schema::create('biodata_pendaftar', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('applicant_id'); $t->string('full_name'); });
        Schema::create('jenis_tagihan', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('tagihan_pendaftar', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('applicant_id'); $t->unsignedBigInteger('bill_type_id');
            $t->decimal('total_amount'); $t->decimal('paid_amount'); $t->decimal('remaining_amount'); $t->string('status'); $t->timestamps();
        });
        Schema::create('transaksi_pembayaran', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('bill_id'); $t->decimal('amount'); $t->string('status');
            $t->unsignedBigInteger('verified_by')->nullable(); $t->timestamp('verified_at')->nullable();
            $t->unsignedBigInteger('treasurer_received_by')->nullable(); $t->timestamp('treasurer_received_at')->nullable();
            $t->text('treasurer_notes')->nullable(); $t->timestamps();
        });
        DB::table('pengguna')->insert([['id'=>1,'name'=>'Bendahara Uji','phone'=>'080000000001'],['id'=>2,'name'=>'Siswa Uji','phone'=>'080000000002']]);
        DB::table('pendaftar')->insert(['id'=>1,'user_id'=>2,'registration_number'=>'SPMB2028-UJI']);
        DB::table('jenis_tagihan')->insert(['id'=>1,'name'=>'Daftar ulang']);
        $user = User::findOrFail(1);
        $user->setRelation('role', new Peran(['name'=>'bendahara']));
        $this->actingAs($user);
        $this->mock(PaymentReceiptNotifier::class)->shouldReceive('send')->once()->andReturnTrue();
        $this->mock(InvoiceEmailNotifier::class)->shouldReceive('send')->once()->andReturnFalse();
    }

    private function payment(string $status): TransaksiPembayaran
    {
        DB::table('tagihan_pendaftar')->insert(['id'=>1,'applicant_id'=>1,'bill_type_id'=>1,'total_amount'=>100000,'paid_amount'=>$status === 'verified' ? 100000 : 0,'remaining_amount'=>$status === 'verified' ? 0 : 100000,'status'=>$status === 'verified' ? 'paid' : 'unpaid']);
        return TransaksiPembayaran::create(['bill_id'=>1,'amount'=>100000,'status'=>$status,'verified_by'=>$status === 'verified' ? 1 : null]);
    }

    public function test_handover_of_an_approved_payment_does_not_send_a_second_whatsapp(): void
    {
        $payment = $this->payment('verified');
        $wa = $this->mock(WhatsappCloudApiService::class);
        $wa->shouldNotReceive('sendDocument');
        $wa->shouldNotReceive('send');
        $this->patch(route('bendahara.pembayaran.receive', $payment))->assertRedirect()->assertSessionHas('success', fn ($message)=>str_contains($message,'tidak dikirim ulang'));
        $this->assertNotNull($payment->fresh()->treasurer_received_at);
        $this->assertEquals(100000, DB::table('tagihan_pendaftar')->value('paid_amount'));
    }

    public function test_direct_receipt_of_a_pending_payment_still_sends_one_whatsapp(): void
    {
        $payment = $this->payment('pending');
        $wa = $this->mock(WhatsappCloudApiService::class);
        $wa->shouldReceive('sendDocument')->once()->withArgs(fn ($phone,$url,$filename,$caption,$event,$parameters)=>$phone === '080000000002' && $event === 'invoice_received');
        $wa->shouldNotReceive('send');
        $this->patch(route('bendahara.pembayaran.receive', $payment))->assertRedirect()->assertSessionHas('success');
        $this->assertSame('verified', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->treasurer_received_at);
        $this->assertEquals(100000, DB::table('tagihan_pendaftar')->value('paid_amount'));
    }
}
