<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\CbtAccessSession;
use App\Models\PesertaTes;
use App\Models\TesMasuk;
use App\Models\KunjunganPendaftar;
use App\Models\TransaksiPembayaran;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalPendaftar = Pendaftar::count();
        $submitted = Pendaftar::where('registration_status', 'submitted')->count();
        $verified = Pendaftar::where('registration_status', 'verified')->count();
        $accepted = Pendaftar::where('registration_status', 'accepted')->count();
        $rejected = Pendaftar::where('registration_status', 'rejected')->count();
        $reRegistered = Pendaftar::where('registration_status', 're_registered')->count();
        $draft = Pendaftar::where('registration_status', 'draft')->count();
        $needsCorrection = Pendaftar::where('correction_status', 'requested')->count();
        $correctionsReturned = Pendaftar::where('correction_status', 'resubmitted')->count();
        $readyForReview = Pendaftar::where('registration_status', 'submitted')
            ->where(fn ($query) => $query->whereNull('correction_status')->orWhere('correction_status', 'resubmitted'))
            ->count();

        $activeCbtSessions = CbtAccessSession::where('status', 'open')
            ->whereNull('closed_at')
            ->where('expires_at', '>=', now())
            ->count();
        $cbtTestId = TesMasuk::where('test_name', 'Tes CBT')->value('id');
        $cbtCompleted = $cbtTestId
            ? PesertaTes::where('test_id', $cbtTestId)->where('attendance', true)->count()
            : 0;
        $testParticipants = PesertaTes::where('attendance', true)
            ->distinct('applicant_id')
            ->count('applicant_id');
        $visitsToday = KunjunganPendaftar::whereDate('visited_at', today())->count();
        $pendingPayments = TransaksiPembayaran::where('status', 'pending')->count();
        $recentVisits = KunjunganPendaftar::with('penerima')
            ->latest('visited_at')
            ->take(5)
            ->get();
        $activityChart = collect(range(11, 0))->map(function ($monthsAgo) {
            $date = Carbon::today()->subMonths($monthsAgo);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();
            $monthNames = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            return [
                'label' => $monthNames[(int) $date->format('n')],
                'year' => $date->format('Y'),
                'visits' => KunjunganPendaftar::whereBetween('visited_at', [$start, $end])->count(),
                'applicants' => Pendaftar::whereBetween('created_at', [$start, $end])->count(),
            ];
        });

        // Pendaftar terbaru
        $recentApplicants = Pendaftar::with(['biodata', 'jurusan1'])
            ->whereIn('registration_status', ['submitted', 'verified', 'accepted', 'rejected', 're_registered'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        return view('panitia.dashboard.index', compact(
            'totalPendaftar', 'submitted', 'verified', 'accepted', 'rejected', 'reRegistered', 'draft',
            'needsCorrection', 'correctionsReturned', 'readyForReview', 'activeCbtSessions',
            'cbtCompleted', 'testParticipants', 'recentApplicants', 'visitsToday', 'pendingPayments',
            'recentVisits', 'activityChart'
        ));
    }
}
