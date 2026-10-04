<?php

namespace Tests\Unit;

use App\Support\RegistrationApprovedMessage;
use Tests\TestCase;

class RegistrationApprovedMessageTest extends TestCase
{
    public function test_cheerful_message_preserves_registration_and_test_details(): void
    {
        $message = RegistrationApprovedMessage::make('Ilham Sompe', 'SPMB2028-0011', 'Sabtu, 09 Januari 2027 · 08:00 WIB', 'https://example.com/peserta/dashboard');
        foreach (['Hai, Ilham Sompe!', '🎉', 'SPMB2028-0011', 'Sabtu, 09 Januari 2027 · 08:00 WIB', 'Kampus E', '30 menit', 'https://example.com/peserta/dashboard'] as $detail) {
            $this->assertStringContainsString($detail, $message);
        }
        $this->assertStringNotContainsString('diterima sebagai siswa', $message);
    }

    public function test_no_schedule_does_not_invent_a_test_date(): void
    {
        $message = RegistrationApprovedMessage::make('Siswa', 'TEST-1', null, 'https://example.com');
        $this->assertStringContainsString('setelah ditetapkan sekolah', $message);
        $this->assertStringNotContainsString('30 menit', $message);
    }
}
