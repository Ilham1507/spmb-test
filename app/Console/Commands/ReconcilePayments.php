<?php

namespace App\Console\Commands;

use App\Models\PaymentCheckout;
use App\Services\MidtransClient;
use App\Services\PaymentCheckoutService;
use Illuminate\Console\Command;
use Throwable;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile {--order= : Periksa satu order ID} {--limit=100}';
    protected $description = 'Cocokkan status pembayaran dengan Midtrans tanpa membuat pembayaran baru';

    public function handle(PaymentCheckoutService $service, MidtransClient $client): int
    {
        if (! $client->ready()) {
            $this->error('Midtrans belum diaktifkan atau konfigurasi belum lengkap.');
            return self::FAILURE;
        }
        $query = PaymentCheckout::query();
        if ($this->option('order')) {
            $query->where('order_id', $this->option('order'));
        } else {
            $query->whereNotNull('active_bill_id');
        }
        $failed = false;
        foreach ($query->orderBy('checked_at')->limit(max(1, min(500, (int) $this->option('limit'))))->get() as $checkout) {
            try {
                $result = $service->refresh($checkout);
                $this->line($result->order_id.' : '.$result->status);
            } catch (Throwable $exception) {
                $this->error($checkout->order_id.' : belum dapat dikonfirmasi');
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
