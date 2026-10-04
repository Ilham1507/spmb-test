<?php

namespace App\Services;

use App\Models\User;
use App\Models\TransaksiPembayaran;
use App\Support\FeeCategory;
use Illuminate\Support\Facades\Log;

class PaymentReceiptNotifier
{
    public function __construct(private WhatsappCloudApiService $whatsapp) {}

    /**
     * Staff receipt notifications are intentionally disabled to avoid redundant
     * messages and potential WhatsApp charges. Keep the existing call sites
     * compatible; student receipts and approval requests are separate flows.
     */
    public function send(TransaksiPembayaran $transaction): bool
    {
        return true;
    }

    /**
     * Notify the teacher/panitia who received the applicant's visit as soon
     * as a student uploads a transfer proof. This is deliberately separate
     * from send(): the payment is still pending and must not be described as
     * verified to either staff or the student.
     */
    public function notifyApprovalNeeded(TransaksiPembayaran $transaction, bool $isResubmission = false): bool
    {
        try {
            $transaction->loadMissing([
                'tagihan.jenisTagihan',
                'tagihan.pendaftar.biodata',
                'tagihan.pendaftar.user',
                'tagihan.pendaftar.kunjungan.penerima',
            ]);

            $bill = $transaction->tagihan;
            $applicant = $bill?->pendaftar;
            $visit = $applicant?->kunjunganPenerimaanUtama();
            $student = $applicant?->biodata?->full_name ?? $applicant?->user?->name ?? 'Calon siswa';
            $feeNames = $this->feeSummary($transaction);
            $registrationNumber = $applicant?->registration_number ?: '-';
            $amount = number_format((float) $transaction->amount, 0, ',', '.');
            $receiver = $visit?->penerima?->name ?? 'Panitia SPMB';
            $approvalTargets = $this->approvalTargets($visit?->penerima?->phone);
            if ($approvalTargets === []) {
                throw new \RuntimeException('Nomor WhatsApp petugas approval belum tersedia.');
            }

            foreach ($approvalTargets as $target) {
                $this->whatsapp->sendNotification($target['phone'], \App\Support\WhatsappGreeting::opening()."\n\n"
                    .($isResubmission
                        ? "Bukti transfer ulang menunggu approval.\n\n"
                        : "Bukti transfer baru menunggu approval.\n\n")
                    ."Siswa: {$student}\nNo. pendaftaran: {$registrationNumber}\nBiaya: {$feeNames}\nNominal: Rp {$amount}\nMetode: Transfer\nPenerima kunjungan: {$receiver}\n\n"
                    ."Silakan periksa bukti dan setujui pembayaran di:\n{$target['url']}",
                    'transfer_pending', [$isResubmission ? 'Bukti transfer ulang menunggu approval.' : 'Bukti transfer baru menunggu approval.',
                        $student, $registrationNumber, $feeNames, $amount, $receiver, $target['url']]);
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Notifikasi bukti pembayaran baru ke penerima kunjungan gagal dikirim.', [
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Students choose fee groups on their payment screen, so WhatsApp must
     * describe those groups instead of exposing one underlying fee detail.
     */
    private function feeSummary(TransaksiPembayaran $transaction): string
    {
        $items = collect($transaction->selected_items ?? [])
            ->pluck('name')
            ->filter(fn ($name) => filled($name))
            ->unique()
            ->values();

        if ($items->isEmpty()) {
            return $transaction->tagihan?->jenisTagihan?->name ?? 'Tagihan sekolah';
        }

        $paymentType = $transaction->tagihan?->jenisTagihan?->name ?? 'Tagihan sekolah';
        $isReRegistration = str_contains(strtolower($paymentType), 'daftar ulang')
            || str_contains(strtolower($paymentType), 'du');
        if (! $isReRegistration) {
            return $items->count() === 1
                ? (string) $items->first()
                : $paymentType.' — '.$items->count().' rincian biaya terpilih';
        }

        $categoriesByItem = collect($transaction->tagihan?->rincian_biaya ?? [])
            ->mapWithKeys(function ($item) {
                $name = trim((string) ($item['name'] ?? ''));
                if ($name === '') {
                    return [];
                }

                $category = trim((string) ($item['category'] ?? '')) ?: FeeCategory::for($name);

                return [$name => $category];
            });
        $groups = $items
            ->map(fn ($name) => $categoriesByItem[$name] ?? FeeCategory::for((string) $name))
            ->filter()
            ->unique()
            ->values();

        if ($groups->count() === 1) {
            return $paymentType.' — '.$groups->first();
        }

        if ($groups->count() <= 3) {
            return $paymentType.' — '.$groups->implode(', ');
        }

        return $paymentType.' — '.$groups->count().' kelompok biaya terpilih';
    }

    /**
     * Panitia, bendahara, and admin share the approval lane. A phone number
     * appears once only, even when one user carries more than one role.
     *
     * @return array<int, array{phone: string, url: string}>
     */
    private function approvalTargets(?string $visitReceiverPhone): array
    {
        $targets = [];
        $addTarget = function (?string $phone, string $route) use (&$targets): void {
            $phone = trim((string) $phone);
            if ($phone === '') {
                return;
            }

            $key = preg_replace('/\D+/', '', $phone) ?: $phone;
            $targets[$key] = ['phone' => $phone, 'url' => route($route)];
        };

        // Keep the visit receiver in the loop, even if they are not assigned
        // one of the standard approval roles.
        $addTarget($visitReceiverPhone, 'panitia.pembayaran.index');

        User::query()
            ->with('role')
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['panitia', 'bendahara', 'admin']))
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get()
            ->each(function (User $user) use ($addTarget): void {
                $route = match ($user->role?->name) {
                    'bendahara' => 'bendahara.pembayaran.index',
                    'admin' => 'admin.pembayaran.index',
                    default => 'panitia.pembayaran.index',
                };
                $addTarget($user->phone, $route);
            });

        if ($targets === []) {
            $addTarget(config('services.panitia.whatsapp_number'), 'panitia.pembayaran.index');
        }

        return array_values($targets);
    }
}
