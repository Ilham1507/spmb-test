<?php

namespace Tests\Unit;

use App\Http\Controllers\Peserta\ReviewController;
use App\Services\WhatsappCloudApiService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ReviewNotificationTest extends TestCase
{
    public function test_whatsapp_is_sent_only_after_the_response_finishes(): void
    {
        $sent = 0;
        $whatsapp = Mockery::mock(WhatsappCloudApiService::class);
        $whatsapp->shouldReceive('sendNotification')->once()->with('08123456789', 'Formulir baru', 'form_submitted', [])
            ->andReturnUsing(function () use (&$sent) { $sent++; });
        $this->controller()->notify($whatsapp, '08123456789');
        $this->assertSame(0, $sent);
        $this->app->terminate();
        $this->assertSame(1, $sent);
    }

    public function test_provider_failure_does_not_fail_registration(): void
    {
        $whatsapp = Mockery::mock(WhatsappCloudApiService::class);
        $whatsapp->shouldReceive('sendNotification')->once()->andThrow(new \RuntimeException('Provider timeout'));
        Log::shouldReceive('warning')->once()->withArgs(fn ($message, $context) => $context['applicant_id'] === 17);
        $this->controller()->notify($whatsapp, '08123456789');
        $this->app->terminate();
        $this->assertTrue(true);
    }

    private function controller(): ReviewController
    {
        return new class extends ReviewController {
            public function notify(WhatsappCloudApiService $whatsapp, string $target): void
            {
                $this->notifyAfterResponse($whatsapp, $target, 'Formulir baru', 17);
            }
        };
    }
}
