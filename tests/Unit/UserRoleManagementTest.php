<?php

namespace Tests\Unit;

use App\Models\Peran;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32))]);
        Schema::create('peran', function ($table) {
            $table->id(); $table->string('name'); $table->string('description')->nullable(); $table->timestamps();
        });
        Schema::create('pengguna', function ($table) {
            $table->id(); $table->string('name'); $table->string('phone'); $table->string('password');
            $table->unsignedBigInteger('role_id'); $table->timestamps();
        });
    }

    private function account(string $role): User
    {
        $peran = Peran::firstOrCreate(['name' => $role]);
        return User::forceCreate(['name' => 'Akun '.$role, 'phone' => '08123456789', 'password' => 'password-123', 'role_id' => $peran->id]);
    }

    public function test_admin_can_promote_registered_student_without_changing_credentials(): void
    {
        $admin = $this->account('admin');
        $student = $this->account('peserta');
        $password = $student->password;
        foreach (['panitia', 'bendahara', 'kepala_sekolah', 'admin', 'peserta'] as $role) {
            $this->actingAs($admin)->patch(route('admin.users.role.update', $student), ['role' => $role])->assertRedirect();
            $this->assertSame($role, $student->fresh()->role->name);
            $this->assertSame($password, $student->fresh()->password);
            $this->assertSame('08123456789', $student->fresh()->phone);
        }
    }

    public function test_non_admin_cannot_change_roles(): void
    {
        $student = $this->account('peserta');
        $this->actingAs($student)->patch(route('admin.users.role.update', $student), ['role' => 'admin']);
        $this->assertSame('peserta', $student->fresh()->role->name);
    }

    public function test_admin_cannot_demote_self_or_assign_unknown_role(): void
    {
        $admin = $this->account('admin');
        $this->actingAs($admin)->patch(route('admin.users.role.update', $admin), ['role' => 'peserta'])->assertSessionHas('error');
        $this->assertSame('admin', $admin->fresh()->role->name);
        $this->patch(route('admin.users.role.update', $admin), ['role' => 'superuser'])->assertSessionHasErrors('role');
    }

    public function test_public_registration_does_not_use_submitted_role(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Auth/RegisteredUserController.php'));
        $this->assertStringContainsString("where('name', 'peserta')", $source);
        $this->assertStringNotContainsString("\$request->role", $source);
    }
}
