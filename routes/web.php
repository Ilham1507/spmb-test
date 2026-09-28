<?php

use App\Http\Controllers\Landing\HomeController;
use App\Http\Controllers\Landing\ProfilController;
use App\Http\Controllers\Landing\JurusanController;
use App\Http\Controllers\Landing\KontakController;
use App\Http\Controllers\Landing\PendaftaranController;
use App\Http\Controllers\Landing\SchoolPageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// One-time, token-protected data replacement for the isolated Railway test
// database. This route is removed immediately after the sync completes.
Route::post('/_internal/railway-sync/{token}', function (Request $request, string $token) {
    abort_unless(app()->environment('production') && hash_equals((string) config('services.railway_import.token'), $token), 404);

    $request->validate(['dump' => ['required', 'file', 'mimes:sql,txt', 'max:51200']]);
    $sql = file_get_contents($request->file('dump')->getRealPath());

    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    try {
        $tables = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $database = DB::getDatabaseName();
        $tableKey = 'Tables_in_'.$database;

        foreach ($tables as $table) {
            $name = $table->{$tableKey};

            if ($name !== 'migrations') {
                DB::unprepared('DELETE FROM `'.str_replace('`', '``', $name).'`');
            }
        }

        DB::getPdo()->exec($sql);
    } finally {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    return response()->json(['ok' => true, 'message' => 'Data Railway sudah disinkronkan.']);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Public Landing Pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/profil', [ProfilController::class, 'index'])->name('profil');
Route::get('/jurusan', [JurusanController::class, 'index'])->name('jurusan');
Route::get('/konsentrasi/{jurusan}', [JurusanController::class, 'show'])->name('konsentrasi.show');
Route::get('/biaya', [PendaftaranController::class, 'biaya'])->name('biaya');
Route::get('/alur', [PendaftaranController::class, 'alur'])->name('alur');
Route::get('/kontak', [KontakController::class, 'index'])->name('kontak');
Route::get('/faq', [HomeController::class, 'faq'])->name('faq');
Route::get('/pengumuman', [HomeController::class, 'pengumuman'])->name('pengumuman');
Route::get('/pengumuman/sorotan/{slug}', [HomeController::class, 'artikelSorotan'])->name('pengumuman.sorotan');
Route::get('/pengumuman/{pengumuman}', [HomeController::class, 'artikel'])->name('pengumuman.artikel');
Route::get('/tentang/{page}', [SchoolPageController::class, 'show'])->whereIn('page', ['pimpinan', 'sejarah', 'sambutan-kepala-sekolah'])->name('tentang.page');
Route::get('/pendidikan/{page}', [SchoolPageController::class, 'show'])->whereIn('page', ['program-studi', 'fasilitas'])->name('pendidikan.page');

// Redirect generic dashboard to role-specific dashboard
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    } elseif ($user->hasRole('panitia')) {
        return redirect()->route('panitia.dashboard');
    } elseif ($user->hasRole('bendahara')) {
        return redirect()->route('bendahara.dashboard');
    } elseif ($user->hasRole('kepala_sekolah')) {
        return redirect()->route('kepala-sekolah.dashboard');
    } else {
        return redirect()->route('peserta.dashboard');
    }
})->middleware('auth')->name('dashboard');

// Auth Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/phone/verify', [ProfileController::class, 'verifyPhoneChange'])->middleware('throttle:8,1')->name('profile.phone.verify');
    Route::post('/profile/phone/resend', [ProfileController::class, 'resendPhoneChangeCode'])->middleware('throttle:3,1')->name('profile.phone.resend');
    Route::patch('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
