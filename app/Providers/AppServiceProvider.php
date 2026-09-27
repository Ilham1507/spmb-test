<?php

namespace App\Providers;

use App\Models\SystemSetting;
use App\Models\Pendaftar;
use App\Models\BiodataPendaftar;
use App\Models\AlamatPendaftar;
use App\Models\DataAyah;
use App\Models\DataIbu;
use App\Models\DataWali;
use App\Models\SekolahAsal;
use App\Models\KontakPendaftar;
use App\Models\GelombangJurusan;
use App\Models\PesertaTes;
use App\Models\HasilPemeriksaanKesehatanPendaftar;
use App\Models\HasilUkurSeragamPendaftar;
use App\Observers\AuditTrailObserver;
use App\Support\SpmbConfiguration;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $observer = AuditTrailObserver::class;
        foreach ([Pendaftar::class, BiodataPendaftar::class, AlamatPendaftar::class, DataAyah::class, DataIbu::class, DataWali::class, SekolahAsal::class, KontakPendaftar::class, GelombangJurusan::class, PesertaTes::class, HasilPemeriksaanKesehatanPendaftar::class, HasilUkurSeragamPendaftar::class] as $model) {
            $model::observe($observer);
        }
        // Free localhost.run tunnels terminate TLS without forwarding a scheme header.
        if (! $this->app->runningInConsole()
            && in_array(request()->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true)
            && str_ends_with(request()->getHost(), '.lhr.life')) {
            request()->server->set('HTTPS', 'on');
        }

        View::composer('*', function ($view) {
            $settings = Schema::hasTable('system_settings') ? SystemSetting::publicValues() : SystemSetting::defaults();
            $activeYear = Schema::hasTable('tahun_ajaran') ? TahunAjaran::query()->where('is_active', true)->first() : null;
            $configuration = Schema::hasTable('pengaturan_spmb') ? SpmbConfiguration::forAcademicYear($activeYear?->id) : null;
            $view->with('siteSettings', $settings)->with('activeAcademicYear', $activeYear)->with('spmbConfiguration', $configuration);
        });
    }
}
