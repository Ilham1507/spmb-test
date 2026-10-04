<?php

namespace Tests\Unit;

use App\Services\WhatsappCloudApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsappMetaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.whatsapp.provider' => 'auto', 'services.whatsapp.waslah_token' => 'legacy-unused',
            'services.whatsapp.phone_number_id' => '123456', 'services.whatsapp.access_token' => 'test-only',
            'services.whatsapp.app_secret' => 'test-signature', 'services.whatsapp.verify_token' => 'test-verify']);
        Http::preventStrayRequests();
    }

    public function test_text_and_pdf_go_to_meta_even_with_legacy_token(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]])]);
        $service = new WhatsappCloudApiService;
        $service->send('081247075160', 'Pesan uji');
        $service->sendDocument('081247075160', 'https://example.com/proof.pdf', 'Bukti.pdf', 'Bukti uji');
        Http::assertSentCount(2);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'graph.facebook.com') && $r['type'] === 'document'
            && $r['document']['link'] === 'https://example.com/proof.pdf' && $r['document']['filename'] === 'Bukti.pdf');
        Http::assertSent(fn ($r) => $r['type'] === 'text' && $r['to'] === '6281247075160');
    }

    public function test_closed_window_uses_approved_notification_template(): void
    {
        config(['services.whatsapp.templates.notification' => 'spmb_info']);
        Http::fake(['graph.facebook.com/*' => Http::sequence()->push(['error' => ['code' => 131047]], 400)->push(['messages' => [['id' => 'wamid.test']]])]);
        (new WhatsappCloudApiService)->send('081247075160', 'Pesan uji');
        Http::assertSent(fn ($r) => $r['type'] === 'template' && $r['template']['name'] === 'spmb_info');
        Http::assertSentCount(2);
    }

    public function test_closed_window_pdf_uses_document_header_template(): void
    {
        config(['services.whatsapp.templates.invoice' => 'spmb_invoice']);
        Http::fake(['graph.facebook.com/*' => Http::sequence()->push(['error' => ['code' => 131047]], 400)->push(['messages' => [['id' => 'wamid.test']]])]);
        (new WhatsappCloudApiService)->sendDocument('081247075160', 'https://example.com/proof.pdf', 'Bukti.pdf');
        Http::assertSent(fn ($r) => $r['type'] === 'template' && $r['template']['components'][0]['parameters'][0]['document']['filename'] === 'Bukti.pdf');
    }

    public function test_no_template_is_an_explicit_failure_not_waslah_fallback(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['code' => 131047]], 400)]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Di luar sesi 24 jam');
        (new WhatsappCloudApiService)->send('081247075160', 'Pesan uji');
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        $this->postJson('/api/webhooks/whatsapp', ['object' => 'whatsapp_business_account'])->assertForbidden();
        config(['services.whatsapp.app_secret' => '']);
        $this->postJson('/api/webhooks/whatsapp', ['object' => 'whatsapp_business_account'])->assertForbidden();
    }

    public function test_signed_incoming_message_is_stored_once(): void
    {
        (require base_path('database/migrations/2026_10_04_120000_create_whatsapp_messages_table.php'))->up();
        $payload = ['object' => 'whatsapp_business_account', 'entry' => [['changes' => [['value' => ['messages' => [[
            'id' => 'wamid.unique', 'from' => '6281247075160', 'type' => 'text', 'text' => ['body' => 'Halo uji'],
        ]]]]]]]];
        $raw = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $raw, 'test-signature');
        foreach ([1, 2] as $attempt) {
            $this->call('POST', '/api/webhooks/whatsapp', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $signature], $raw)->assertOk();
        }
        $this->assertDatabaseCount('whatsapp_messages', 1);
        $this->assertDatabaseHas('whatsapp_messages', ['wa_message_id' => 'wamid.unique', 'body' => 'Halo uji']);
    }

    public function test_verify_challenge(): void
    {
        $this->get('/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=test-verify&hub.challenge=1234')->assertOk()->assertSee('1234');
        $this->get('/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=1234')->assertForbidden();
    }
}
