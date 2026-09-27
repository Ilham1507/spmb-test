<?php

namespace App\Services;

use App\Models\PaymentCheckout;
use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use App\Support\PaymentQuote;
use App\Support\PaymentChannel;
use App\Support\RegistrationFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class PaymentCheckoutService
{
    public function __construct(private MidtransClient $client, private PaymentReceiptNotifier $notifier, private WhatsappCloudApiService $whatsapp) {}

    public function start(TagihanPendaftar $bill, int $expectedAmount, ?array $selectedItems = null): PaymentCheckout
    {
        if (! $this->client->ready()) {
            throw ValidationException::withMessages(['payment' => 'Pembayaran online belum tersedia. Hubungi bendahara sekolah.']);
        }
        [$checkout, $created] = DB::transaction(function () use ($bill, $expectedAmount, $selectedItems) {
            $bill = TagihanPendaftar::lockForUpdate()->findOrFail($bill->id);
            $existing = $bill->checkouts()->whereNotNull('active_bill_id')->first();
            if ($existing?->expires_at?->isPast()) {
                $existing->update([
                    'status' => 'closed', 'provider_status' => 'expire',
                    'active_bill_id' => null, 'checked_at' => now(),
                ]);
                $existing = null;
            }
            if ($existing) {
                if ($existing->production !== (bool) config('payments.midtrans.production') || $existing->merchant_id !== config('payments.midtrans.merchant_id')) {
                    throw ValidationException::withMessages(['payment' => 'Akun pembayaran berubah. Hubungi bendahara untuk memeriksa pembayaran sebelumnya.']);
                }
                return [$existing, false];
            }
            $this->assertPayable($bill);
            $quote = PaymentQuote::forBill($bill, $selectedItems, $expectedAmount);
            if (! $quote['valid_selection']) {
                throw ValidationException::withMessages(['selected_items' => 'Pilih biaya yang ingin dibayar.']);
            }
            if ($expectedAmount !== $quote['amount'] || $quote['amount'] < 1) {
                throw ValidationException::withMessages(['payment' => 'Pilihan biaya atau nominal berubah. Muat ulang halaman sebelum membayar.']);
            }

            return [PaymentCheckout::create([
                'bill_id' => $bill->id, 'active_bill_id' => $bill->id,
                'order_id' => 'SPMB-'.Str::uuid(),
                'production' => (bool) config('payments.midtrans.production'),
                'merchant_id' => config('payments.midtrans.merchant_id'),
                'amount' => $quote['amount'], 'discount' => $quote['discount'], 'promotion_name' => $quote['promotion_name'] ?? null,
                'selected_items' => $quote['selected_items'],
                'bill_total' => $bill->total_amount, 'bill_paid' => $bill->paid_amount,
                'expires_at' => now()->addMinutes((int) config('payments.midtrans.expiry_minutes', 10)), 'status' => 'creating',
            ]), true];
        }, 3);

        if ($created) {
            try {
                $url = $this->client->create($checkout);
                // A webhook may already have arrived; do not overwrite its status.
                PaymentCheckout::whereKey($checkout->id)->where('status', 'creating')
                    ->update(['redirect_url' => $url, 'status' => 'pending']);
            } catch (Throwable $exception) {
                PaymentCheckout::whereKey($checkout->id)->where('status', 'creating')->update(['status' => 'needs_review']);
                Log::warning('Pembuatan checkout belum pasti; jangan buat order pengganti.', ['order_id' => $checkout->order_id]);
                throw ValidationException::withMessages(['payment' => 'Pembayaran belum bisa dibuka. Jangan transfer dulu; cek status atau hubungi bendahara.']);
            }
        }

        return $checkout->refresh();
    }

    public function assertPayable(TagihanPendaftar $bill): void
    {
        if ($bill->status === 'paid' || (float) $bill->remaining_amount <= 0) {
            throw ValidationException::withMessages(['payment' => 'Tagihan ini sudah lunas.']);
        }
        if ($bill->transaksi()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['payment' => 'Bukti pembayaran masih dicek. Tidak perlu membayar lagi.']);
        }
        $name = strtolower((string) $bill->jenisTagihan?->name);
    }

    public function cancel(PaymentCheckout $checkout): PaymentCheckout
    {
        if (! $checkout->active_bill_id || $checkout->status !== 'pending') {
            throw ValidationException::withMessages(['payment' => 'Pembayaran ini tidak dapat dibatalkan.']);
        }
        // Do not release the local lock until Midtrans confirms the cancellation.
        $this->client->cancel($checkout);

        return DB::transaction(function () use ($checkout) {
            $checkout = PaymentCheckout::lockForUpdate()->findOrFail($checkout->id);
            if (! $checkout->active_bill_id || $checkout->transaction_id) {
                throw ValidationException::withMessages(['payment' => 'Pembayaran sudah diproses. Cek status terlebih dahulu.']);
            }
            $checkout->update([
                'status' => 'closed', 'provider_status' => 'cancel', 'active_bill_id' => null, 'checked_at' => now(),
            ]);

            return $checkout;
        }, 3);
    }

    public function refresh(PaymentCheckout $checkout): PaymentCheckout
    {
        $payload = $this->client->status($checkout);
        if ($payload === null) {
            $checkout->update(['checked_at' => now()]);
            return $checkout;
        }

        // Only this authenticated server-to-server result is allowed to settle a bill.
        $valid = ($payload['order_id'] ?? null) === $checkout->order_id
            && ($payload['merchant_id'] ?? null) === $checkout->merchant_id
            && ($payload['currency'] ?? null) === 'IDR'
            && preg_match('/^\d+(?:\.00)?$/', (string) ($payload['gross_amount'] ?? ''))
            && (float) $payload['gross_amount'] === (float) $checkout->amount;
        if (! $valid) {
            Log::warning('Identitas atau nominal pembayaran tidak cocok.', ['order_id' => $checkout->order_id]);
            throw new RuntimeException('Data pembayaran tidak cocok; perlu pemeriksaan bendahara.');
        }

        $settledTransaction = null;
        $approvalTransaction = null;
        $checkout = DB::transaction(function () use ($checkout, $payload, &$settledTransaction, &$approvalTransaction) {
            $checkout = PaymentCheckout::lockForUpdate()->findOrFail($checkout->id);
            $status = (string) ($payload['transaction_status'] ?? '');
            $checkout->checked_at = now();
            $checkout->provider_status = $status;
            $channel = PaymentChannel::fromPayload($payload);
            $checkout->provider_payment_type = $channel['type'] ?: null;
            $checkout->provider_bank = $channel['bank'] ?: null;
            // Never apply a second credit or silently reverse a refund/chargeback.
            if ($checkout->transaction_id) {
                if (in_array($status, ['refund', 'partial_refund', 'chargeback', 'partial_chargeback'], true)) {
                    $checkout->status = 'needs_review';
                    Log::warning('Pembayaran perlu rekonsiliasi pengembalian dana.', ['order_id' => $checkout->order_id]);
                }
                $checkout->save();
                return $checkout;
            }
            if ($status === 'settlement' && ($payload['fraud_status'] ?? 'accept') === 'accept'
                && in_array($payload['payment_type'] ?? '', ['bank_transfer', 'echannel', 'qris', 'gopay', 'shopeepay', 'dana', 'ovo'], true)
                && filled($payload['transaction_id'] ?? null)) {
                $bill = TagihanPendaftar::lockForUpdate()->findOrFail($checkout->bill_id);
                // Changed bills or late settlements are reviewed instead of over-crediting.
                if ($bill->status === 'paid' || (float) $bill->total_amount !== (float) $checkout->bill_total
                    || (float) $bill->paid_amount !== (float) $checkout->bill_paid
                    || (float) $checkout->amount > (float) $bill->remaining_amount - (float) $checkout->discount) {
                    $checkout->status = 'needs_review';
                    $checkout->save();
                    Log::warning('Pembayaran diterima penyedia tetapi tagihan berubah.', ['order_id' => $checkout->order_id]);
                    return $checkout;
                }
                $bill->loadMissing('jenisTagihan');
                $requiresApproval = $this->isRegistrationFee($bill)
                    || ($this->isReRegistrationFee($bill) && ! $bill->transaksi()->where('status', 'verified')->exists());
                $transaction = TransaksiPembayaran::create([
                    'bill_id' => $bill->id, 'transaction_number' => $checkout->order_id,
                    'reference_number' => $payload['transaction_id'], 'payment_date' => now(),
                    'amount' => $checkout->amount, 'selected_items' => $checkout->selected_items,
                    'discount_amount' => $checkout->discount, 'promotion_name' => $checkout->promotion_name,
                    'payment_method' => in_array($payload['payment_type'] ?? '', ['bank_transfer', 'echannel'], true) ? 'transfer' : 'digital', 'status' => $requiresApproval ? 'pending' : 'verified',
                    'verified_at' => $requiresApproval ? null : now(),
                    'notes' => $requiresApproval
                        ? "Pembayaran {$channel['label']} sudah diterima; menunggu persetujuan petugas."
                        : "Pembayaran {$channel['label']} dikonfirmasi otomatis.",
                ]);
                if ($requiresApproval) {
                    $approvalTransaction = $transaction;
                } else {
                    $settledTransaction = $transaction;
                    $total = (float) $bill->total_amount - (float) $checkout->discount;
                    $paid = (float) $bill->paid_amount + (float) $checkout->amount;
                    $remaining = max(0, $total - $paid);
                    $bill->update([
                        'total_amount' => $total, 'paid_amount' => $paid,
                        'remaining_amount' => $remaining, 'status' => $remaining <= 0 ? 'paid' : 'partial',
                    ]);
                }
                $checkout->fill(['status' => $requiresApproval ? 'awaiting_approval' : 'paid', 'active_bill_id' => null,
                    'transaction_id' => $transaction->id, 'provider_transaction_id' => $payload['transaction_id']]);
            } elseif (in_array($status, ['expire', 'cancel', 'deny', 'failure'], true)) {
                $checkout->fill(['status' => 'closed', 'active_bill_id' => null]);
            } elseif ($status === 'pending' && in_array($checkout->status, ['closed', 'needs_review'], true)) {
                // A delayed status response must not reopen a closed/reviewed order.
            } elseif ($status === 'pending') {
                $checkout->status = 'pending';
            } else {
                // Unknown, fraud/challenge, or a non-VA success must be reviewed.
                $checkout->status = 'needs_review';
            }
            $checkout->save();

            return $checkout;
        }, 3);

        if ($settledTransaction) {
            $this->notifier->send($settledTransaction);
        }
        if ($approvalTransaction) {
            $this->notifyRegistrationPaymentPending($approvalTransaction);
        }

        return $checkout;
    }

    private function isRegistrationFee(TagihanPendaftar $bill): bool
    {
        $name = strtolower((string) $bill->jenisTagihan?->name);
        return str_contains($name, 'formulir') || str_contains($name, 'pendaftaran');
    }

    private function isReRegistrationFee(TagihanPendaftar $bill): bool
    {
        $name = strtolower((string) $bill->jenisTagihan?->name);
        return str_contains($name, 'daftar ulang') || str_contains($name, 'du');
    }

    private function notifyRegistrationPaymentPending(TransaksiPembayaran $transaction): void
    {
        try {
            $transaction->loadMissing('tagihan.pendaftar.kunjungan.penerima', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user');
            $pendaftar = $transaction->tagihan?->pendaftar;
            $receiver = $pendaftar?->kunjunganPenerimaanUtama()?->penerima;
            $adminTarget = $receiver?->phone ?: (string) config('services.panitia.whatsapp_number');
            $name = $pendaftar?->biodata?->full_name ?? $pendaftar?->user?->name ?? 'Calon siswa';
            $feeName = $transaction->tagihan?->jenisTagihan?->name ?? 'tagihan SPMB';
            $registrationNumber = $pendaftar?->registration_number ?? '-';
            $receiverName = $receiver?->name ?? 'Panitia SPMB';
            $channel = PaymentChannel::label($transaction->checkout?->provider_payment_type, $transaction->checkout?->provider_bank);
            $amount = number_format((float) $transaction->amount, 0, ',', '.');
            if ($adminTarget !== '') {
                $this->whatsapp->send($adminTarget, "Halo {$receiverName},\n\nAda pembayaran {$feeName} yang perlu diperiksa.\n\nSiswa: {$name}\nNo. pendaftaran: {$registrationNumber}\nNominal: Rp {$amount}\nMetode: {$channel}\nPenerima kunjungan: {$receiverName}\n\nSilakan buka menu Approval Pembayaran untuk menyetujui pembayaran. Setelah disetujui, siswa akan menerima notifikasi WhatsApp.");
            }
        } catch (Throwable $exception) {
            Log::warning('Notifikasi approval pembayaran formulir VA gagal dikirim.', ['transaction_id' => $transaction->id, 'error' => $exception->getMessage()]);
        }
    }
}
