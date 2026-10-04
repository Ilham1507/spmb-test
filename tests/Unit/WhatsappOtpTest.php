<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ParticipantActivationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsappOtpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32))]);
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('phone');
            $table->string('password'); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); $table->string('token'); $table->timestamp('created_at');
        });
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id(); $table->string('key')->unique(); $table->text('value')->nullable();
            $table->string('group')->nullable(); $table->timestamps();
        });
    }

    public function test_code_is_hashed_six_digits_and_replaced_on_resend(): void
    {
        $user = User::create(['name' => 'Siswa Uji', 'phone' => '081234567890', 'password' => 'old-password']);
        $service = new ParticipantActivationService;
        $first = $service->createCode($user);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $first);
        $oldHash = DB::table('password_reset_tokens')->value('token');
        $this->assertNotSame($first, $oldHash);
        $this->assertTrue(Hash::check($first, $oldHash));
        $second = $service->createCode($user);
        $this->assertTrue(Hash::check($second, DB::table('password_reset_tokens')->value('token')));
        $this->assertDatabaseCount('password_reset_tokens', 1);
    }

    public function test_code_entry_screen_is_available_without_a_secret_in_the_url(): void
    {
        $this->get('/reset-password/kode')->assertOk()->assertSee('Kode verifikasi WhatsApp');
        $this->get('/forgot-password')->assertOk()->assertSee('Sudah menerima kode?');
    }

    public function test_valid_code_resets_password_and_cannot_be_reused(): void
    {
        $user = User::create(['name' => 'Siswa Uji', 'phone' => '081234567890', 'password' => 'old-password']);
        $code = (new ParticipantActivationService)->createCode($user);
        $data = ['phone' => $user->phone, 'token' => $code, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->post('/reset-password', $data)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->post('/reset-password', $data)->assertSessionHasErrors('phone');
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = User::create(['name' => 'Siswa Uji', 'phone' => '081234567890', 'password' => 'old-password']);
        $code = (new ParticipantActivationService)->createCode($user);
        DB::table('password_reset_tokens')->update(['created_at' => now()->subMinutes(6)]);
        $this->post('/reset-password', ['phone' => $user->phone, 'token' => $code, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertSessionHasErrors('phone');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_five_invalid_attempts_block_even_a_correct_code(): void
    {
        $user = User::create(['name' => 'Siswa Uji', 'phone' => '081234567890', 'password' => 'old-password']);
        $code = (new ParticipantActivationService)->createCode($user);
        $data = ['phone' => $user->phone, 'token' => 'not-a-code', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        foreach (range(1, 5) as $attempt) $this->post('/reset-password', $data)->assertSessionHasErrors('phone');
        $data['token'] = $code;
        $this->post('/reset-password', $data)->assertSessionHasErrors('phone');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
