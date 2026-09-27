<?php

namespace App\Http\Controllers\KepalaSekolah;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $statusCounts = Pendaftar::query()
            ->selectRaw('registration_status, count(*) as total')
            ->groupBy('registration_status')
            ->pluck('total', 'registration_status');

        $months = collect(range(5, 0))->map(function (int $monthsAgo) {
            $month = Carbon::today()->subMonthsNoOverflow($monthsAgo);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            return [
                'label' => $month->translatedFormat('M'),
                'applicants' => Pendaftar::whereBetween('created_at', [$start, $end])->count(),
                'receipts' => (float) TransaksiPembayaran::where('status', 'verified')
                    ->whereBetween('payment_date', [$start, $end])->sum('amount'),
            ];
        });

        $verifiedTotal = (float) TransaksiPembayaran::where('status', 'verified')->sum('amount');
        $targetTotal = (float) TagihanPendaftar::sum('total_amount');

        return view('kepala-sekolah.dashboard.index', [
            'totalApplicants' => Pendaftar::count(),
            'submitted' => (int) ($statusCounts['submitted'] ?? 0),
            'accepted' => (int) ($statusCounts['accepted'] ?? 0),
            'reRegistered' => (int) ($statusCounts['re_registered'] ?? 0),
            'pendingPayments' => TransaksiPembayaran::where('status', 'pending')->count(),
            'verifiedTotal' => $verifiedTotal,
            'targetTotal' => $targetTotal,
            'financeProgress' => $targetTotal > 0 ? min(100, (int) round(($verifiedTotal / $targetTotal) * 100)) : 0,
            'months' => $months,
            'chartMaxApplicants' => max(1, (int) $months->max('applicants')),
            'chartMaxReceipts' => max(1, (float) $months->max('receipts')),
            'latestApplicants' => Pendaftar::with(['biodata', 'jurusan1'])
                ->latest('updated_at')->limit(5)->get(),
        ]);
    }
}
