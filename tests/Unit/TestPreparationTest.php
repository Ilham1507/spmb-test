<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\PengaturanController;
use App\Models\SystemSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TestPreparationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('group');
            $table->timestamps();
        });
    }

    public function test_structured_preparation_cards_are_saved(): void
    {
        $request = Request::create('/admin/informasi-tes', 'POST', ['items' => [
            ['title' => ' HP & internet ', 'body' => ' Bawa HP yang cukup baterai. '],
            ['title' => 'Berkas', 'body' => 'Bawa dokumen pendukung.', 'icon' => 'document'],
        ]]);
        app(PengaturanController::class)->saveTestPreparation($request);
        $this->assertSame([
            ['title' => 'HP & internet', 'body' => 'Bawa HP yang cukup baterai.', 'icon' => 'phone'],
            ['title' => 'Berkas', 'body' => 'Bawa dokumen pendukung.', 'icon' => 'document'],
        ], json_decode(SystemSetting::values()['test_preparation_items'], true));
    }

    public function test_incomplete_card_is_rejected_without_saving(): void
    {
        try {
            app(PengaturanController::class)->saveTestPreparation(Request::create('/', 'POST', [
                'items' => [['title' => 'HP', 'body' => '']],
            ]));
            $this->fail('Incomplete preparation must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items.0.body', $exception->errors());
            $this->assertSame(0, SystemSetting::count());
        }
    }

    public function test_selected_icon_does_not_depend_on_card_position(): void
    {
        app(PengaturanController::class)->saveTestPreparation(Request::create('/', 'POST', [
            'items' => [['title' => 'Alat tulis', 'body' => 'Bawa pensil.', 'icon' => 'pen']],
        ]));
        $item = json_decode(SystemSetting::values()['test_preparation_items'], true)[0];
        $this->assertSame('pen', $item['icon']);
        $this->assertSame('pen', \App\Support\TestPreparationIcons::resolve($item['icon'], 7));
        $html = view('components.test-preparation-icon', ['icon' => $item['icon']])->render();
        $this->assertStringContainsString('m16 3 5 5', $html);
    }

    public function test_unknown_icon_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(PengaturanController::class)->saveTestPreparation(Request::create('/', 'POST', [
            'items' => [['title' => 'HP', 'body' => 'Bawa HP.', 'icon' => 'unknown']],
        ]));
    }

    public function test_existing_items_keep_their_original_icons(): void
    {
        $this->assertSame('phone', \App\Support\TestPreparationIcons::resolve(null, 0));
        $this->assertSame('shirt', \App\Support\TestPreparationIcons::resolve(null, 1));
        $this->assertSame('family', \App\Support\TestPreparationIcons::resolve(null, 2));
        $this->assertSame('document', \App\Support\TestPreparationIcons::resolve(null, 4));
    }
}
