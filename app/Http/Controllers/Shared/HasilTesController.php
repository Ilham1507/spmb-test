<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\CbtAccessSession;
use App\Models\SystemSetting;
use App\Models\PesertaTes;
use App\Models\TesMasuk;
use App\Support\Pagination;
use App\Models\HasilPemeriksaanKesehatanPendaftar;
use App\Models\HasilUkurSeragamPendaftar;
use App\Models\ItemPemeriksaanKesehatan;
use App\Models\UkuranSeragam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HasilTesController extends Controller
{
    public function index(Request $request)
    {
        $isParticipant = $request->routeIs('peserta.*');
        $layout = $isParticipant ? 'layouts.peserta' : match (true) {
            $request->routeIs('admin.*') => 'layouts.admin',
            $request->routeIs('bendahara.*') => 'layouts.bendahara',
            default => 'layouts.panitia',
        };

        if ($isParticipant) {
            $pendaftar = Pendaftar::studentApplicants()->with(['biodata', 'jurusan1', 'preferredTestSchedule'])
                ->where('user_id', Auth::id())
                ->first();
            $activeCbtSession = $pendaftar
                ? CbtAccessSession::where('applicant_id', $pendaftar->id)
                    ->where('status', 'open')->whereNull('closed_at')->where('expires_at', '>=', now())
                    ->latest()->first()
                : null;
            $completedCbt = $pendaftar
                ? PesertaTes::with('tes')->where('applicant_id', $pendaftar->id)
                    ->where('attendance', true)->whereHas('tes', fn ($query) => $query->where('test_name', 'Tes CBT'))
                    ->latest()->first()
                : null;
            $announcementAt = $pendaftar?->preferredTestSchedule?->tanggal_mulai
                ? \Illuminate\Support\Carbon::parse($pendaftar->preferredTestSchedule->tanggal_mulai)->addDays(3)->startOfDay()
                : null;
            $selectionDecided = $pendaftar && in_array($pendaftar->registration_status, ['accepted', 'rejected', 're_registered'], true);
            $mayViewResult = $completedCbt && $announcementAt && now()->greaterThanOrEqualTo($announcementAt) && $selectionDecided;
            $results = $mayViewResult
                ? PesertaTes::with('tes')->where('applicant_id', $pendaftar->id)->where('attendance', true)->get()->sortBy('tes.test_name')
                : collect();

            $defaultPreparation = [['title' => 'HP & internet', 'body' => 'Bawa HP yang cukup baterai dan pastikan internet dapat digunakan.'], ['title' => 'Berpakaian rapi', 'body' => 'Gunakan pakaian yang rapi dan sopan saat hadir di sekolah.'], ['title' => 'Bersama orang tua/wali', 'body' => 'Datang bersama orang tua atau wali untuk mengikuti proses tes.'],
            ['title' => 'Berkas pendukung', 'body' => 'Bawa Kartu Keluarga, Akta Kelahiran, dan sertifikat prestasi jika ada.']];
            $savedPreparation = json_decode(SystemSetting::values()['test_preparation_items'] ?? '', true);
            $testPreparation = is_array($savedPreparation) && $savedPreparation ? $savedPreparation : $defaultPreparation;

            return view('shared.hasil-tes.index', compact('layout', 'isParticipant', 'pendaftar', 'activeCbtSession', 'completedCbt', 'announcementAt', 'selectionDecided', 'mayViewResult', 'results', 'testPreparation'));
        }

        $tests = TesMasuk::orderBy('test_date')->orderBy('test_name')->get();
        $testId = $request->integer('test_id') ?: null;
        $baseResults = PesertaTes::with('tes')->where('attendance', true)
            ->when($testId, fn ($query) => $query->where('test_id', $testId));
        $applicantIds = $baseResults->pluck('applicant_id')->unique();
        $applicants = Pendaftar::studentApplicants()->with(['biodata', 'jurusan1'])
            ->whereIn('id', $applicantIds)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($inner) use ($search) {
                    $inner->where('registration_number', 'like', "%{$search}%")
                        ->orWhereHas('biodata', fn ($biodata) => $biodata->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->latest()->paginate(Pagination::perPage())->withQueryString();
        $resultsByApplicant = PesertaTes::with('tes')->where('attendance', true)
            ->whereIn('applicant_id', $applicants->pluck('id'))
            ->get()->groupBy('applicant_id');
        $canViewNotes = !$request->routeIs('bendahara.*');

        $detailRoute = $request->routeIs('admin.*') ? 'admin.hasil-tes.show' : ($request->routeIs('bendahara.*') ? 'bendahara.hasil-tes.show' : 'panitia.hasil-tes.show');

        return view('shared.hasil-tes.index', compact('layout', 'isParticipant', 'tests', 'testId', 'applicants', 'resultsByApplicant', 'canViewNotes', 'detailRoute'));
    }

    public function show(Request $request, Pendaftar $pendaftar)
    {
        $layout = $request->routeIs('admin.*') ? 'layouts.admin' : ($request->routeIs('bendahara.*') ? 'layouts.bendahara' : 'layouts.panitia');
        $canViewNotes = !$request->routeIs('bendahara.*');
        $pendaftar->load(['biodata', 'jurusan1']);
        $results = PesertaTes::with('tes')->where('applicant_id', $pendaftar->id)->where('attendance', true)->get()->keyBy('test_id');
        $primaryResults = $results->filter(fn (PesertaTes $result) => !in_array($result->tes?->test_name, ['Tes Ukuran Seragam', 'Tes Kesehatan'], true));
        $tests = TesMasuk::whereIn('id', $results->keys())->get()->keyBy('id');
        $healthResults = HasilPemeriksaanKesehatanPendaftar::where('applicant_id', $pendaftar->id)->orderBy('health_check_item_id')->get();
        $healthItems = ItemPemeriksaanKesehatan::whereIn('id', $healthResults->pluck('health_check_item_id'))->get()->keyBy('id');
        $uniformResult = HasilUkurSeragamPendaftar::where('applicant_id', $pendaftar->id)->first();
        $uniformSize = $uniformResult?->uniform_size_id ? UkuranSeragam::find($uniformResult->uniform_size_id) : null;
        $backRoute = $request->routeIs('admin.*') ? 'admin.hasil-tes.index' : ($request->routeIs('bendahara.*') ? 'bendahara.hasil-tes.index' : 'panitia.hasil-tes.index');

        return view('shared.hasil-tes.show', compact('layout', 'pendaftar', 'results', 'primaryResults', 'tests', 'healthResults', 'healthItems', 'uniformResult', 'uniformSize', 'canViewNotes', 'backRoute'));
    }
}
