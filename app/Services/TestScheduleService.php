<?php

namespace App\Services;

use App\Models\JadwalSpmb;
use App\Models\Pendaftar;
use App\Support\WhatsappGreeting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TestScheduleService
{
    public static function date(string $date): string
    {
        return Carbon::parse($date)->locale('id')->translatedFormat('l, d F Y · H:i').' WIB';
    }

    public function update(JadwalSpmb $schedule, array $data): void
    {
        [$ids, $oldDate, $newDate, $details] = DB::transaction(function () use ($schedule, $data) {
            $schedule = JadwalSpmb::whereKey($schedule->id)->lockForUpdate()->firstOrFail();
            $ids = Pendaftar::where('preferred_test_schedule_id', $schedule->id)->pluck('id')->all();
            if ($ids && (int) $data['tahun_ajaran_id'] !== (int) $schedule->tahun_ajaran_id) {
                throw ValidationException::withMessages(['tahun_ajaran_id' => 'Jadwal yang sudah dipilih siswa tidak boleh dipindah ke tahun ajaran berbeda.']);
            }
            $oldDate = self::date($schedule->tanggal_mulai);
            $schedule->fill($data);
            $changed = $schedule->isDirty(['tanggal_mulai', 'tanggal_selesai', 'kegiatan', 'keterangan']);
            $schedule->save();

            return [$changed ? $ids : [], $oldDate, self::date($schedule->tanggal_mulai), $schedule->keterangan];
        });
        $this->notify($ids, $oldDate, $newDate, $details);
    }

    public function move(JadwalSpmb $source, JadwalSpmb $target): int
    {
        [$ids, $oldDate, $newDate, $details] = DB::transaction(function () use ($source, $target) {
            $schedules = JadwalSpmb::whereIn('id', [$source->id, $target->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $schedules->get($source->id);
            $target = $schedules->get($target->id);
            if (! $source || ! $target || $source->id === $target->id
                || $source->tahun_ajaran_id != $target->tahun_ajaran_id
                || ! $target->available_for_student_selection
                || Carbon::parse($target->tanggal_mulai)->lt(now()->startOfDay())) {
                throw ValidationException::withMessages(['replacement_schedule_id' => 'Pilih jadwal pengganti yang dibuka, belum lewat, dan berada pada tahun ajaran yang sama.']);
            }
            $ids = Pendaftar::where('preferred_test_schedule_id', $source->id)->lockForUpdate()->pluck('id')->all();
            Pendaftar::whereIn('id', $ids)->update(['preferred_test_schedule_id' => $target->id, 'preferred_test_selected_at' => now()]);
            $source->update(['available_for_student_selection' => false]);

            return [$ids, self::date($source->tanggal_mulai), self::date($target->tanggal_mulai), $target->keterangan];
        });
        $this->notify($ids, $oldDate, $newDate, $details);

        return count($ids);
    }

    protected function notify(array $ids, string $oldDate, string $newDate, ?string $details): void
    {
        if (! $ids) {
            return;
        }
        app()->terminating(function () use ($ids, $oldDate, $newDate, $details) {
            Pendaftar::with(['user', 'biodata'])->whereIn('id', $ids)->chunkById(50, function ($students) use ($oldDate, $newDate, $details) {
                foreach ($students as $student) {
                    if (! $student->user?->phone) {
                        continue;
                    }
                    $name = $student->biodata?->full_name ?: $student->user->name;
                    $message = WhatsappGreeting::opening()."\n\n"
                        ."Informasi perubahan jadwal Tes SPMB.\n\n"
                        ."Siswa: {$name}\nNo. pendaftaran: {$student->registration_number}\n"
                        ."Jadwal sebelumnya: {$oldDate}\nJadwal terbaru: {$newDate}\n"
                        ."Lokasi: Kampus E SMK Muhammadiyah 4 Cileungsi\n"
                        .($details ? "Keterangan: {$details}\n" : '')
                        ."\nSilakan hadir sesuai jadwal terbaru. Tidak perlu mengirim ulang formulir.\n"
                        .'Pantau jadwal di: '.route('peserta.dashboard')."\n\nTerima kasih.";
                    try {
                        app(WhatsappCloudApiService::class)->sendNotification($student->user->phone, $message, 'schedule_changed', [
                            $name, $student->registration_number, $oldDate, $newDate, $details ?: 'Ikuti jadwal terbaru.',
                        ]);
                    } catch (\Throwable $exception) {
                        Log::warning('Notifikasi perubahan jadwal Tes SPMB gagal.', ['applicant_id' => $student->id, 'error' => $exception->getMessage()]);
                    }
                }
            });
        });
    }
}
