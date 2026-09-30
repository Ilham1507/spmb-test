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
        // Keep login responsive on Railway: the old dashboard issued a
        // separate database query for every status.
        $applicantStats = Pendaftar::query()->selectRaw(<<<'SQL'
            COUNT(*) as total,
            SUM(registration_status = 'submitted') as submitted,
            SUM(registration_status = 'verified') as verified,
            SUM(registration_status = 'accepted') as accepted,
            SUM(registration_status = 'rejected') as rejected,
            SUM(registration_status = 're_registered') as re_registered,
            SUM(registration_status = 'draft') as draft,
            SUM(correction_status = 'requested') as needs_correction,
            SUM(correction_status = 'resubmitted') as corrections_returned,
            SUM(registration_status = 'submitted' AND (correction_status IS NULL OR correction_status = 'resubmitted')) as ready_for_review
        SQL)->first();

        $totalPendaftar = (int) ($applicantStats?->total ?? 0);
        $submitted = (int) ($applicantStats?->submitted ?? 0);
        $verified = (int) ($applicantStats?->verified ?? 0);
        $accepted = (int) ($applicantStats?->accepted ?? 0);
        $rejected = (int) ($applicantStats?->rejected ?? 0);
        $reRegistered = (int) ($applicantStats?->re_registered ?? 0);
        $draft = (int) ($applicantStats?->draft ?? 0);
        $needsCorrection = (int) ($applicantStats?->needs_correction ?? 0);
        $correctionsReturned = (int) ($applicantStats?->corrections_returned ?? 0);
        $readyForReview = (int) ($applicantStats?->ready_for_review ?? 0);

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
        $chartStart = Carbon::today()->subMonths(11)->startOfMonth();
        $visitCounts = KunjunganPendaftar::query()
            ->where('visited_at', '>=', $chartStart)
            ->selectRaw("DATE_FORMAT(visited_at, '%Y-%m') as period, COUNT(*) as total")
            ->groupBy('period')
            ->pluck('total', 'period');
        $applicantCounts = Pendaftar::query()
            ->where('created_at', '>=', $chartStart)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period, COUNT(*) as total")
            ->groupBy('period')
            ->pluck('total', 'period');

        $activityChart = collect(range(11, 0))->map(function ($monthsAgo) use ($visitCounts, $applicantCounts) {
            $date = Carbon::today()->subMonths($monthsAgo);
            $monthNames = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $period = $date->format('Y-m');

            return [
                'label' => $monthNames[(int) $date->format('n')],
                'year' => $date->format('Y'),
                'visits' => (int) ($visitCounts->get($period) ?? 0),
                'applicants' => (int) ($applicantCounts->get($period) ?? 0),
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
