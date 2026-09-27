<?php

namespace App\Console\Commands;

use App\Models\HasilSeleksi;
use App\Services\WhatsappCloudApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTestResultNotifications extends Command
{
    protected $signature = 'spmb:send-test-result-notifications {--dry-run : Tampilkan kandidat tanpa mengirim WhatsApp}';
    protected $description = 'Kirim hasil Tes SPMB pada pukul 07.00 WIB di hari ketiga setelah tes';

    public function handle(WhatsappCloudApiService $whatsapp): int
    {
        $now = now('Asia/Jakarta');
        $results = HasilSeleksi::with(['pendaftar.user', 'pendaftar.kontak', 'pendaftar.preferredTestSchedule', 'major'])
            ->whereIn('status', ['accepted', 'rejected'])
            ->whereNull('whatsapp_result_notified_at')
            ->get();

        foreach ($results as $result) {
            $pendaftar = $result->pendaftar;
            $schedule = $pendaftar?->preferredTestSchedule;
            if (!$pendaftar || !$schedule) {
                continue;
            }

            $sendAt = Carbon::parse($schedule->tanggal_mulai, 'Asia/Jakarta')
                ->addDays(3)
                ->startOfDay()
                ->setTime(7, 0);

            if ($now->lt($sendAt)) {
                continue;
            }

            $studentName = $pendaftar->biodata?->full_name ?? $pendaftar->user?->name ?? 'Calon siswa';
            $phone = $pendaftar->user?->phone ?? $pendaftar->kontak?->phone;
            if (!$phone) {
                Log::warning('Notifikasi hasil Tes SPMB tidak dapat dikirim karena nomor siswa kosong.', ['result_id' => $result->id]);
                continue;
            }

            $outcome = $result->status === 'accepted'
                ? "Hasil: Diterima\nJurusan: " . ($result->major?->name ?? 'Jurusan pilihan')
                : 'Hasil: Belum diterima';
            $message = "Halo {$studentName},\n\n"
                . "Hasil Tes SPMB sudah diumumkan.\n"
                . "No. pendaftaran: {$pendaftar->registration_number}\n"
                . "{$outcome}\n\n"
                . 'Silakan buka dashboard SPMB untuk melihat informasi selanjutnya.';

            if ($this->option('dry-run')) {
                $this->line("Siap kirim: {$pendaftar->registration_number} pada {$sendAt->format('d-m-Y H:i')} WIB");
                continue;
            }

            try {
                $whatsapp->send((string) $phone, $message);
                $result->update(['whatsapp_result_notified_at' => $now]);
                $this->info("Terkirim: {$pendaftar->registration_number}");
            } catch (Throwable $exception) {
                Log::warning('Notifikasi hasil Tes SPMB gagal dikirim.', [
                    'result_id' => $result->id,
                    'applicant_id' => $pendaftar->id,
                    'error' => $exception->getMessage(),
                ]);
                $this->warn("Belum terkirim: {$pendaftar->registration_number}");
            }
        }

        return self::SUCCESS;
    }
}
