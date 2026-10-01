<?php

namespace Tests\Unit;

use App\Models\JadwalSpmb;
use App\Services\TestScheduleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TestScheduleServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('jadwal_spmb', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tahun_ajaran_id');
            $table->string('kegiatan');
            $table->dateTime('tanggal_mulai');
            $table->dateTime('tanggal_selesai')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('available_for_student_selection');
            $table->timestamps();
        });
        Schema::create('pendaftar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('preferred_test_schedule_id');
            $table->dateTime('preferred_test_selected_at')->nullable();
            $table->string('registration_status');
            $table->timestamps();
        });
    }

    public function test_day_and_month_are_always_indonesian(): void
    {
        $this->assertSame('Sabtu, 10 April 2027 · 08:00 WIB', TestScheduleService::date('2027-04-10 08:00:00'));
        $this->assertStringContainsString('Mei', TestScheduleService::date('2027-05-08 08:00:00'));
    }

    public function test_edit_notifies_only_affected_students_and_noop_does_not_notify(): void
    {
        $source = $this->schedule();
        $other = $this->schedule();
        $this->student(1, $source->id);
        $this->student(2, $other->id);
        $service = $this->service();
        $data = $source->only(['tahun_ajaran_id', 'kegiatan', 'tanggal_mulai', 'tanggal_selesai', 'keterangan', 'available_for_student_selection']);
        $service->update($source, $data);
        $this->assertSame([], $service->recipients);
        $data['tanggal_mulai'] = now()->addMonths(2)->format('Y-m-d H:i:s');
        $service->update($source, $data);
        $this->assertSame([1], $service->recipients);
        $this->assertSame($data['tanggal_mulai'], $source->fresh()->tanggal_mulai);
    }

    public function test_move_preserves_registration_and_closes_old_date(): void
    {
        $source = $this->schedule();
        $target = $this->schedule();
        $other = $this->schedule();
        $this->student(1, $source->id);
        $this->student(2, $source->id);
        $this->student(3, $other->id);
        $service = $this->service();
        $this->assertSame(2, $service->move($source, $target));
        $this->assertSame([1, 2], $service->recipients);
        $this->assertFalse((bool) $source->fresh()->available_for_student_selection);
        $this->assertSame(2, DB::table('pendaftar')->where('preferred_test_schedule_id', $target->id)->where('registration_status', 'submitted')->count());
        $this->assertSame($other->id, DB::table('pendaftar')->where('id', 3)->value('preferred_test_schedule_id'));
    }

    public function test_cross_year_move_is_rejected_without_changing_students(): void
    {
        $source = $this->schedule();
        $target = $this->schedule(99);
        $this->student(1, $source->id);
        try {
            $this->service()->move($source, $target);
            $this->fail('Cross-year move accepted');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('replacement_schedule_id', $exception->errors());
        }
        $this->assertSame($source->id, DB::table('pendaftar')->where('id', 1)->value('preferred_test_schedule_id'));
    }

    private function schedule(int $year = 2): JadwalSpmb
    {
        return JadwalSpmb::create(['tahun_ajaran_id' => $year, 'kegiatan' => 'Tes SPMB', 'tanggal_mulai' => now()->addMonth()->format('Y-m-d H:i:s'), 'available_for_student_selection' => true]);
    }

    private function student(int $id, int $schedule): void
    {
        DB::table('pendaftar')->insert(['id' => $id, 'preferred_test_schedule_id' => $schedule, 'registration_status' => 'submitted']);
    }

    private function service(): TestScheduleService
    {
        return new class extends TestScheduleService
        {
            public array $recipients = [];

            protected function notify(array $ids, string $oldDate, string $newDate, ?string $details): void
            {
                $this->recipients = array_merge($this->recipients, $ids);
            }
        };
    }
}
