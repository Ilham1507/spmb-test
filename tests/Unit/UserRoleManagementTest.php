<?php

namespace Tests\Unit;

use App\Models\Peran;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
        Schema::create('pendaftar', function ($table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->timestamps();
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

    public function test_admin_can_edit_and_reset_password_for_every_role_without_sending_messages(): void
    {
        Http::fake();
        $admin = $this->account('admin');
        foreach (['peserta', 'panitia', 'bendahara', 'kepala_sekolah', 'admin'] as $role) {
            $user = $this->account($role);
            $user->phone = '0812'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT);
            $user->save();
            $this->actingAs($admin)->patch(route('admin.users.update', $user), [
                'name' => 'Nama Baru', 'phone' => $user->phone, 'role' => $role, 'password' => 'new-password-123',
            ])->assertSessionHasNoErrors()->assertSessionHas('success');
            $this->assertSame('Nama Baru', $user->fresh()->name);
            $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        }
        Http::assertNothingSent();
    }

    public function test_consolidated_edit_cannot_demote_current_admin(): void
    {
        $admin = $this->account('admin');
        $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
            'name' => $admin->name, 'phone' => $admin->phone, 'role' => 'peserta',
        ])->assertSessionHas('error');
        $this->assertSame('admin', $admin->fresh()->role->name);
    }

    public function test_delete_preserves_registration_history_and_current_admin(): void
    {
        $admin = $this->account('admin');
        $student = $this->account('peserta');
        \Illuminate\Support\Facades\DB::table('pendaftar')->insert(['user_id' => $student->id]);
        $this->actingAs($admin)->delete(route('admin.users.destroy', $student))->assertSessionHas('error');
        $this->assertDatabaseHas('pengguna', ['id' => $student->id]);
        $this->delete(route('admin.users.destroy', $admin))->assertSessionHas('error');
        $this->assertDatabaseHas('pengguna', ['id' => $admin->id]);
        $unused = $this->account('panitia');
        $this->delete(route('admin.users.destroy', $unused))->assertSessionHas('success');
        $this->assertDatabaseMissing('pengguna', ['id' => $unused->id]);
    }

    public function test_old_student_menu_redirects_to_user_management(): void
    {
        $this->actingAs($this->account('admin'))->get(route('admin.siswa.index', ['search' => 'Ilham']))
            ->assertRedirect(route('admin.users.index', ['search' => 'Ilham']));
        $view = file_get_contents(resource_path('views/admin/pengguna/index.blade.php'));
        $this->assertStringNotContainsString('Daftar sendiri dari halaman depan', $view);
        $this->assertStringContainsString('user-management-table', $view);
        $this->assertStringNotContainsString("in_array(\$roleName, ['panitia', 'bendahara', 'kepala_sekolah']", $view);
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));
        $this->assertStringNotContainsString("[route('admin.siswa.index'), 'Data Siswa'", $layout);
    }
}
