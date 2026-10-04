<?php

namespace Tests\Unit;

use App\Models\TransaksiPembayaran;
use App\Services\PaymentReceiptNotifier;
use App\Services\WhatsappCloudApiService;
use Mockery;
use PHPUnit\Framework\TestCase;

class PaymentReceiptNotifierTest extends TestCase
{
    public function test_staff_receipt_does_not_send_whatsapp_or_load_payment_relations(): void
    {
        $whatsapp = Mockery::mock(WhatsappCloudApiService::class);
        $whatsapp->shouldNotReceive('sendNotification');
        $transaction = Mockery::mock(TransaksiPembayaran::class);
        $transaction->shouldNotReceive('loadMissing');

        try {
            $this->assertTrue((new PaymentReceiptNotifier($whatsapp))->send($transaction));
        } finally {
            Mockery::close();
        }
    }
}
