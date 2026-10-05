<?php

namespace Tests\Unit;

use App\Http\Controllers\Panitia\PendaftarController;
use App\Models\{Pendaftar, Peran, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Http, Schema, Storage};
use Mockery;
use Tests\TestCase;

class ApplicantRoleAndAvatarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        Http::preventStrayRequests();
        Schema::create('peran', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('description')->nullable(); $table->timestamps();
        });
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('phone')->nullable();
            $table->string('password')->nullable(); $table->unsignedBigInteger('role_id')->nullable();
            $table->string('profile_photo_path')->nullable(); $table->string('avatar_choice')->nullable(); $table->timestamps();
        });
        Schema::create('pendaftar', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('registration_number');
            $table->string('registration_status')->default('draft'); $table->integer('major_choice_1')->nullable();
            $table->integer('wave_id')->nullable(); $table->timestamps();
        });
        Schema::create('biodata_pendaftar', function (Blueprint $table) {
            $table->id(); $table->integer('applicant_id'); $table->string('full_name')->nullable();
        });
    }

    private function user(string $role, string $name = 'Sultan Azehre'): User
    {
        $peran = Peran::firstOrCreate(['name' => $role]);
        return User::create(['name' => $name, 'role_id' => $peran->id, 'phone' => '08123456'.str_pad((string) (User::count() + 1), 4, '0', STR_PAD_LEFT)]);
    }

    public function test_changing_role_excludes_staff_from_counts_and_list_but_keeps_history(): void
    {
        $admin = $this->user('admin', 'Admin');
        $student = $this->user('peserta');
        $registration = Pendaftar::create(['user_id' => $student->id, 'registration_number' => 'SPMB2028-0001']);
        $this->actingAs($admin);
        foreach (['panitia', 'bendahara', 'kepala_sekolah', 'admin'] as $role) {
            $this->patch('/admin/users/'.$student->id.'/role', ['role' => $role])->assertRedirect()->assertSessionHas('success');
            $this->assertSame(0, Pendaftar::studentApplicants()->count());
            $view = app(PendaftarController::class)->index(Request::create('/admin/pendaftar'));
            $this->assertSame(0, $view->getData()['pendaftars']->total());
            $this->assertDatabaseHas('pendaftar', ['id' => $registration->id, 'registration_number' => 'SPMB2028-0001']);
        }
        $this->patch('/admin/users/'.$student->id.'/role', ['role' => 'peserta'])->assertRedirect();
        $this->assertSame(1, Pendaftar::studentApplicants()->count());
        $this->assertSame(1, Pendaftar::count());
        Http::assertNothingSent();
    }

    private function png(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jr1sAAAAASUVORK5CYII=';
    }

    public function test_edit_user_role_also_updates_applicant_population(): void
    {
        $admin = $this->user('admin', 'Admin');
        $student = $this->user('peserta');
        Pendaftar::create(['user_id' => $student->id, 'registration_number' => 'SPMB2028-0001']);
        $this->actingAs($admin)->patch('/admin/users/'.$student->id, [
            'name' => $student->name, 'phone' => $student->phone, 'role' => 'panitia',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame(0, Pendaftar::studentApplicants()->count());
        $this->assertSame(1, Pendaftar::count());
        Http::assertNothingSent();
    }

    public function test_dashboard_counts_charts_and_recent_list_use_student_roles_only(): void
    {
        DB::connection()->getPdo()->sqliteCreateFunction('CHAR_LENGTH', 'strlen');
        Schema::table('biodata_pendaftar', function (Blueprint $table) {
            $table->string('nisn')->nullable(); $table->string('nik')->nullable();
        });
        foreach ([
            'jurusan' => ['name', 'status'], 'gelombang_pendaftaran' => ['status'],
            'jenis_tagihan' => ['name'], 'tagihan_pendaftar' => ['applicant_id', 'bill_type_id', 'total_amount', 'status'],
            'transaksi_pembayaran' => ['bill_id', 'amount', 'status', 'payment_date'],
            'dokumen_pendaftar' => ['applicant_id', 'file_path', 'status'],
            'peserta_tes' => ['applicant_id', 'attendance'], 'kunjungan_pendaftar' => ['visited_at'],
            'alamat_pendaftar' => ['applicant_id', 'distance_range'], 'sekolah_asal' => ['applicant_id', 'school_name'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) $table->string($column)->nullable();
                $table->timestamps();
            });
        }
        DB::table('jurusan')->insert(['id' => 1, 'name' => 'Busana', 'status' => 'aktif']);
        foreach (['peserta', 'panitia'] as $role) {
            $user = $this->user($role);
            Pendaftar::create(['user_id' => $user->id, 'major_choice_1' => 1, 'registration_number' => 'SPMB2028-000'.$user->id]);
        }
        $data = app(\App\Http\Controllers\Admin\DashboardController::class)->index()->getData();
        $this->assertSame(1, $data['totalPendaftar']);
        $this->assertSame(1, $data['statusChart']->sum('value'));
        $this->assertSame(1, $data['majorChart']->sum('value'));
        $this->assertSame(1, $data['monthlyApplicants']->sum('value'));
        $this->assertSame(1, $data['adminActivityChart']->sum('applicants'));
        $this->assertSame(1, $data['latestApplicants']->count());
        $this->assertSame('peserta', $data['latestApplicants']->first()->user->role->name);
    }

    public function test_uploaded_photo_creates_storage_directory_and_updates_profile(): void
    {
        Storage::fake('public');
        $user = $this->user('peserta');
        $this->actingAs($user)->patch('/profile/avatar', ['avatar_crop' => $this->png()])
            ->assertRedirect(route('profile.edit'))->assertSessionHas('status', 'avatar-updated')->assertSessionHasNoErrors();
        $path = $user->fresh()->profile_photo_path;
        $this->assertStringStartsWith('storage/profiles/', $path);
        Storage::disk('public')->assertExists(substr($path, 8));
        $this->assertSame(base64_decode(explode(',', $this->png())[1]), Storage::disk('public')->get(substr($path, 8)));
        Http::assertNothingSent();
    }

    public function test_invalid_image_does_not_overwrite_existing_photo(): void
    {
        Storage::fake('public');
        $user = $this->user('peserta');
        $user->update(['profile_photo_path' => 'images/existing.png']);
        foreach (['data:image/png;base64,'.base64_encode('not an image'), 'data:image/jpeg;base64,'.explode(',', $this->png())[1], 'data:image/png;base64,!!!'] as $invalid) {
            $this->actingAs($user)->from('/profile')->patch('/profile/avatar', ['avatar_crop' => $invalid])
                ->assertRedirect('/profile')->assertSessionHasErrors('avatar_crop');
            $this->assertSame('images/existing.png', $user->fresh()->profile_photo_path);
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_storage_write_returns_form_error_not_500(): void
    {
        $user = $this->user('peserta');
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->once()->andReturn($disk);
        $this->actingAs($user)->from('/profile')->patch('/profile/avatar', ['avatar_crop' => $this->png()])
            ->assertRedirect('/profile')->assertSessionHasErrors('avatar_crop');
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_character_avatar_still_works(): void
    {
        $user = $this->user('panitia');
        $this->actingAs($user)->patch('/profile/avatar', ['avatar_choice' => 'character_7'])
            ->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors();
        $this->assertSame('character_7', $user->fresh()->avatar_choice);
    }
}
