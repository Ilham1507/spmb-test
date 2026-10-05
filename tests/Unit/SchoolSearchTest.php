<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\ReferensiSekolahController;
use App\Http\Controllers\Peserta\SekolahAsalController;
use App\Models\ReferensiSekolah;
use App\Services\OfficialSchoolDirectory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, Http, Schema};
use Tests\TestCase;

class SchoolSearchTest extends TestCase
{
    private const BASE = 'https://referensi.data.kemendikdasmen.go.id/pendidikan/';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::preventStrayRequests();
        Schema::create('referensi_sekolah', function (Blueprint $table) {
            $table->id(); $table->string('npsn')->unique(); $table->string('nama');
            foreach (['bentuk_pendidikan', 'status', 'alamat', 'desa_kelurahan', 'kecamatan', 'kabupaten_kota', 'provinsi'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
    }

    public function test_both_controllers_return_even_one_local_match_without_network(): void
    {
        ReferensiSekolah::create(['npsn' => '20200001', 'nama' => 'SMP Cileungsi', 'bentuk_pendidikan' => 'SMP']);
        foreach ([SekolahAsalController::class, ReferensiSekolahController::class] as $controller) {
            $response = app($controller)->search(Request::create('/search', 'GET', ['q' => 'SMP Cileungsi']));
            $this->assertCount(1, $response->getData(true));
            $this->assertSame('20200001', $response->getData(true)[0]['npsn']);
        }
        Http::assertNothingSent();
    }

    public function test_short_queries_never_call_the_directory(): void
    {
        foreach (['', 's', 'sm'] as $query) {
            $response = app(SekolahAsalController::class)->search(Request::create('/search', 'GET', ['q' => $query]));
            $this->assertSame([], $response->getData(true));
        }
        Http::assertNothingSent();
    }

    private function detail(string $name = 'SMP Baru', string $form = 'SMP'): string
    {
        return "<h4>{$name}</h4><table><tr><td>Bentuk Pendidikan</td><td>:</td><td>{$form}</td></tr>"
            .'<tr><td>Kecamatan/Kota (LN)</td><td>:</td><td>Cileungsi</td></tr></table>';
    }

    public function test_multiword_query_has_one_search_and_at_most_two_details_and_is_cached(): void
    {
        $links = '';
        for ($i = 1; $i <= 10; $i++) $links .= '<a href="pendidikan/npsn/'.(20200000 + $i).'">Sekolah</a>';
        $options = [];
        Http::fake(function ($request, $requestOptions) use ($links, &$options) {
            $options[] = $requestOptions;
            return Http::response(str_contains($request->url(), '/cari/') ? $links : $this->detail());
        });
        $service = app(OfficialSchoolDirectory::class);
        $this->assertCount(2, $service->search('SMP Baru Cileungsi'));
        $this->assertCount(2, $service->search('SMP Baru Cileungsi'));
        Http::assertSentCount(3);
        foreach ($options as $requestOptions) {
            $this->assertLessThanOrEqual(1.25, $requestOptions['timeout']);
            $this->assertLessThanOrEqual(0.5, $requestOptions['connect_timeout']);
        }
        $this->assertSame(2, ReferensiSekolah::count());
    }

    public function test_exact_npsn_uses_only_one_detail_request(): void
    {
        Http::fake([self::BASE.'npsn/20200001' => Http::response($this->detail())]);
        $this->assertCount(1, app(OfficialSchoolDirectory::class)->search('20200001'));
        Http::assertSentCount(1);
    }

    public function test_upstream_timeout_returns_empty_and_applies_shared_cooldown(): void
    {
        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;
            throw new ConnectionException('Upstream timeout');
        });
        $service = app(OfficialSchoolDirectory::class);
        $this->assertCount(0, $service->search('SMP Tidak Ada'));
        $this->assertCount(0, $service->search('MTs Pencarian Lain'));
        $this->assertSame(1, $calls);
        // The shared lock is released even when the directory fails.
        $lock = Cache::lock('official-school-directory', 8);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_busy_directory_lock_does_not_wait_or_call_upstream(): void
    {
        $lock = Cache::lock('official-school-directory', 8);
        $this->assertTrue($lock->get());
        try {
            $this->assertCount(0, app(OfficialSchoolDirectory::class)->search('SMP Lain'));
            Http::assertNothingSent();
        } finally {
            $lock->release();
        }
    }

    public function test_total_deadline_stops_further_requests(): void
    {
        $service = new class extends OfficialSchoolDirectory {
            public float $elapsed = 0;
            protected function monotonicTime(): float { return $this->elapsed; }
        };
        Http::fake(function () use ($service) {
            $service->elapsed = 2.95;
            return Http::response('<a href="pendidikan/npsn/20200001">Sekolah</a>');
        });
        $this->assertCount(0, $service->search('SMP Batas Waktu'));
        Http::assertSentCount(1);
    }

    public function test_cached_school_is_not_returned_after_deactivation(): void
    {
        Http::fake([self::BASE.'npsn/20200001' => Http::response($this->detail())]);
        $service = app(OfficialSchoolDirectory::class);
        $this->assertCount(1, $service->search('20200001'));
        ReferensiSekolah::first()->update(['status' => 'nonaktif']);
        $this->assertCount(0, $service->search('20200001'));
        Http::assertSentCount(1);
    }

    public function test_non_junior_high_school_is_not_imported(): void
    {
        Http::fake([self::BASE.'npsn/20200001' => Http::response($this->detail('SD Baru', 'SD'))]);
        $this->assertCount(0, app(OfficialSchoolDirectory::class)->search('20200001'));
        $this->assertSame(0, ReferensiSekolah::count());
    }
}
