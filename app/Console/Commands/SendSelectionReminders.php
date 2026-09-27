<?php

namespace App\Console\Commands;

use App\Models\Pendaftar;
use App\Services\WhatsappCloudApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSelectionReminders extends Command
{
    protected $signature = 'spmb:send-selection-reminders {--dry-run : Tampilkan penerima tanpa mengirim WhatsApp}';
    protected $description = 'Ingatkan penerima kunjungan untuk menetapkan hasil Tes SPMB sebelum pengumuman';

    public function handle(WhatsappCloudApiService $whatsapp): int
    {
        $now = now('Asia/Jakarta');

        // Pesan hanya dikirim pada pagi hari. Scheduler berjalan tiap menit supaya
        // tetap dapat mengejar pengiriman bila proses server baru hidup sesudah 07.00.
        if ($now->hour !== 7) {
            return self::SUCCESS;
        }

        $pendaftars = Pendaftar::with([
                'biodata',
                'preferredTestSchedule',
                'kunjungan.penerima',
                'hasilSeleksi',
            ])
            ->where('registration_status', 'verified')
            ->whereHas('pesertaTes', fn ($query) => $query->where('attendance', true))
            ->doesntHave('hasilSeleksi')
            ->get();

        foreach ($pendaftars as $pendaftar) {
            $schedule = $pendaftar->preferredTestSchedule;
            if (! $schedule || $pendaftar->selection_reminder_sent_on?->isSameDay($now)) {
                continue;
            }

            $testDay = Carbon::parse($schedule->tanggal_mulai, 'Asia/Jakarta')->startOfDay();
            $resultAt = $testDay->copy()->addDays(3)->setTime(7, 0);

            // Pengingat dimulai pagi setelah tes dan berhenti pada hari pengumuman.
            if (! $now->copy()->startOfDay()->gt($testDay) || $now->gte($resultAt)) {
                continue;
            }

            $visit = $pendaftar->kunjunganPenerimaanUtama();
            $receiver = $visit?->penerima;
            $target = $receiver?->phone ?: (string) config('services.panitia.whatsapp_number');
            if (! $target) {
                Log::warning('Pengingat seleksi Tes SPMB tidak dapat dikirim karena nomor penerima kosong.', [
                    'applicant_id' => $pendaftar->id,
                ]);
                continue;
            }

            $studentName = $pendaftar->biodata?->full_name ?? 'Calon siswa';
            $receiverName = $receiver?->name ?? 'Panitia SPMB';
            $message = "Halo {$receiverName},\n\n"
                . "Pengingat keputusan hasil Tes SPMB\n"
                . "Siswa: {$studentName}\n"
                . "No. pendaftaran: {$pendaftar->registration_number}\n\n"
                . 'Siswa sudah mengikuti tes. Silakan tetapkan hasil seleksi sebelum pengumuman kepada siswa pada pukul 07.00 WIB.';

            if ($this->option('dry-run')) {
                $this->line("Siap ingatkan: {$receiverName} untuk {$pendaftar->registration_number}");
                continue;
            }

            try {
                $whatsapp->send((string) $target, $message);
                $pendaftar->update(['selection_reminder_sent_on' => $now->toDateString()]);
                $this->info("Terkirim: {$pendaftar->registration_number}");
            } catch (Throwable $exception) {
                Log::warning('Pengingat keputusan Tes SPMB gagal dikirim.', [
                    'applicant_id' => $pendaftar->id,
                    'error' => $exception->getMessage(),
                ]);
                $this->warn("Belum terkirim: {$pendaftar->registration_number}");
            }
        }

        return self::SUCCESS;
    }
}
