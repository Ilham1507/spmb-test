<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Peran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsappChatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32)), 'session.driver' => 'array']);
        (require base_path('database/migrations/2026_10_04_120000_create_whatsapp_messages_table.php'))->up();
        (require base_path('database/migrations/2026_10_05_010000_create_whatsapp_chat_replies_table.php'))->up();
        config(['services.whatsapp.phone_number_id' => '123', 'services.whatsapp.access_token' => 'test', 'services.whatsapp.app_secret' => 'test']);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('whatsapp_chat_replies');
        Schema::dropIfExists('whatsapp_messages');
        parent::tearDown();
    }

    private function user(string $role): User
    {
        $user = new User(['name' => 'Test']);
        $user->id = 1;
        $user->setRelation('role', new Peran(['name' => $role]));
        return $user;
    }

    private function incoming(): void
    {
        DB::table('whatsapp_messages')->insert(['wa_message_id' => 'incoming', 'sender_phone' => '628123456789', 'message_type' => 'text', 'body' => '<script>alert(1)</script>', 'payload' => '{}', 'received_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_panitia_cannot_read_or_send_admin_chat(): void
    {
        $this->actingAs($this->user('panitia'))->get('/admin/chat-whatsapp')->assertRedirect(route('login'));
        $this->postJson('/admin/chat-whatsapp', ['phone' => '628123456789', 'body' => 'test'])->assertRedirect(route('login'));
        Http::assertNothingSent();
    }

    public function test_admin_inbox_and_manual_reply_are_persisted(): void
    {
        $this->incoming();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.reply']]])]);
        $this->actingAs($this->user('admin'))->getJson('/admin/chat-whatsapp?phone=628123456789')->assertOk()->assertJsonPath('open', true)->assertJsonPath('messages.0.direction', 'in');
        $this->postJson('/admin/chat-whatsapp', ['phone' => '628123456789', 'body' => 'Selamat pagi'])->assertOk();
        $this->assertDatabaseHas('whatsapp_chat_replies', ['wa_message_id' => 'wamid.reply', 'status' => 'accepted', 'admin_id' => 1]);
        Http::assertSent(fn ($r) => $r['type'] === 'text' && $r['to'] === '628123456789');
        Http::assertSentCount(1);
    }

    public function test_closed_session_never_calls_meta(): void
    {
        $this->incoming();
        DB::table('whatsapp_messages')->update(['received_at' => now()->subHours(25)]);
        $this->actingAs($this->user('admin'))->postJson('/admin/chat-whatsapp', ['phone' => '628123456789', 'body' => 'test'])->assertStatus(422);
        Http::assertNothingSent();
        $this->assertDatabaseCount('whatsapp_chat_replies', 0);
    }

    public function test_unknown_recipient_cannot_be_messaged(): void
    {
        $this->actingAs($this->user('admin'))->postJson('/admin/chat-whatsapp', ['phone' => '628123456789', 'body' => 'test'])->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_admin_page_renders_and_failed_reply_is_not_shown_as_delivered(): void
    {
        $this->incoming();
        $response = $this->actingAs($this->user('admin'))->get('/admin/chat-whatsapp?phone=628123456789');
        $response->assertOk()->assertSee('Kirim balasan', false);
        if ($directory = getenv('WHATSAPP_CHAT_PREVIEW_DIR')) {
            file_put_contents($directory.'/whatsapp-chat.html', $response->getContent());
        }
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Rejected', 'code' => 131047]], 400)]);
        $this->postJson('/admin/chat-whatsapp', ['phone' => '628123456789', 'body' => 'Test'])->assertStatus(502);
        $this->assertDatabaseHas('whatsapp_chat_replies', ['status' => 'failed']);
        Http::assertSentCount(1);
    }

    public function test_delivery_webhook_updates_reply_without_downgrading_read(): void
    {
        DB::table('whatsapp_chat_replies')->insert(['recipient_phone' => '628123456789', 'admin_id' => 1, 'body' => 'Test', 'wa_message_id' => 'wamid.reply', 'status' => 'accepted', 'created_at' => now(), 'updated_at' => now()]);
        foreach (['read', 'sent'] as $status) {
            $payload = ['object' => 'whatsapp_business_account', 'entry' => [['changes' => [['value' => ['statuses' => [['id' => 'wamid.reply', 'status' => $status]]]]]]]];
            $body = json_encode($payload);
            $request = \Illuminate\Http\Request::create('/api/webhooks/whatsapp', 'POST', [], [], [], ['HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'test'), 'CONTENT_TYPE' => 'application/json'], $body);
            app(\App\Http\Controllers\WhatsappWebhookController::class)->receive($request);
        }
        $this->assertDatabaseHas('whatsapp_chat_replies', ['status' => 'read']);
    }

    public function test_automatic_text_and_pdf_are_recorded_in_same_conversation(): void
    {
        $this->incoming();
        config(['services.whatsapp.provider' => 'meta', 'services.whatsapp.templates.invoice' => '']);
        Http::fake(['graph.facebook.com/*' => Http::sequence()
            ->push(['messages' => [['id' => 'wamid.auto-text']]])
            ->push(['messages' => [['id' => 'wamid.auto-pdf']]])]);
        $service = app(\App\Services\WhatsappCloudApiService::class);
        $service->send('08123456789', 'Formulir sudah diverifikasi');
        $service->sendDocument('08123456789', 'https://example.com/invoice.pdf', 'Bukti.pdf', 'Pembayaran diterima');
        $this->assertDatabaseHas('whatsapp_chat_replies', ['admin_id' => 0, 'wa_message_id' => 'wamid.auto-text', 'recipient_phone' => '628123456789']);
        $this->assertDatabaseHas('whatsapp_chat_replies', ['wa_message_id' => 'wamid.auto-pdf', 'body' => "Pembayaran diterima\n\n📄 Bukti.pdf"]);
        $this->actingAs($this->user('admin'))->getJson('/admin/chat-whatsapp?phone=628123456789')->assertOk()->assertJsonCount(3, 'messages');
    }

    public function test_outgoing_only_contact_is_visible_and_otp_is_redacted(): void
    {
        config(['services.whatsapp.provider' => 'meta', 'services.whatsapp.business_account_id' => '', 'services.whatsapp.templates.authentication' => 'test_otp']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.otp']]])]);
        app(\App\Services\WhatsappCloudApiService::class)->sendAuthentication('08123456789', '012345');
        $body = DB::table('whatsapp_chat_replies')->value('body');
        $this->assertStringNotContainsString('012345', $body);
        $this->actingAs($this->user('admin'))->getJson('/admin/chat-whatsapp?phone=628123456789')->assertOk()->assertJsonPath('open', false)->assertJsonCount(1, 'conversations')->assertJsonCount(1, 'messages');
    }

    public function test_contact_names_match_registered_phone_formats(): void
    {
        Schema::create('pengguna', function ($table) {
            $table->id(); $table->string('name'); $table->string('phone');
        });
        try {
            $this->incoming();
            DB::table('pengguna')->insert(['name' => 'Ilham Sompe', 'phone' => '+62 812-3456-789']);
            $this->actingAs($this->user('admin'))->getJson('/admin/chat-whatsapp?phone=628123456789')
                ->assertOk()->assertJsonPath('contactName', 'Ilham Sompe')->assertJsonPath('conversations.0.name', 'Ilham Sompe');
            DB::table('pengguna')->update(['phone' => '08123456789']);
            $this->getJson('/admin/chat-whatsapp?phone=628123456789')->assertJsonPath('contactName', 'Ilham Sompe');
        } finally {
            Schema::dropIfExists('pengguna');
        }
    }
}
