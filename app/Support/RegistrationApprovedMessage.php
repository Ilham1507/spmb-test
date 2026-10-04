<?php

namespace App\Support;

class RegistrationApprovedMessage
{
    public static function make(string $name, string $number, ?string $schedule, string $dashboardUrl): string
    {
        $scheduleInfo = $schedule
            ? "📅 Jadwal tes: {$schedule}\n"
                . "📍 Lokasi: Kampus E SMK Muhammadiyah 4 Cileungsi\n"
                . "Datang 30 menit lebih awal, ya, supaya bisa bersiap dengan nyaman. 😊\n\n"
            : "Jadwal tes akan muncul di dashboard setelah ditetapkan sekolah. Pantau terus, ya! 😊\n\n";

        return WhatsappGreeting::opening()."\n\n"
            . "Hai, {$name}! 👋\n"
            . "Kabar baik! Formulir pendaftaranmu sudah diverifikasi oleh panitia. 🎉\n"
            . "Sekarang kamu bisa bersiap untuk mengikuti Tes SPMB!\n"
            . "No. pendaftaran: {$number}\n\n"
            . $scheduleInfo
            . "Lihat info selanjutnya di dashboard kamu:\n{$dashboardUrl}\n\n"
            . "Semangat persiapannya! Sampai bertemu di sekolah! ✨";
    }
}
