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
            ['title' => 'Berkas', 'body' => 'Bawa dokumen pendukung.'],
        ]]);
        app(PengaturanController::class)->saveTestPreparation($request);
        $this->assertSame([
            ['title' => 'HP & internet', 'body' => 'Bawa HP yang cukup baterai.'],
            ['title' => 'Berkas', 'body' => 'Bawa dokumen pendukung.'],
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
}
