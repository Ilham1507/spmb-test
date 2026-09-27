<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use App\Models\TahunAjaran;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $registrationBills = $this->billTypeQuery(['formulir', 'pendaftaran']);
        $reRegistrationBills = $this->billTypeQuery(['daftar ulang']);
        $verifiedTransactions = TransaksiPembayaran::query()->where('transaksi_pembayaran.status', 'verified');
        $verifiedTotal = (float) (clone $verifiedTransactions)->sum('amount');
        $targetBills = (float) TagihanPendaftar::sum('total_amount');
        $activeAcademicYear = TahunAjaran::query()->where('is_active', true)->first();
        $monthStart = $activeAcademicYear?->start_date
            ? Carbon::parse($activeAcademicYear->start_date)->startOfMonth()
            : Carbon::today()->startOfMonth();
        $monthEnd = $activeAcademicYear?->end_date
            ? Carbon::parse($activeAcademicYear->end_date)->endOfMonth()
            : $monthStart->copy()->addMonths(11)->endOfMonth();
        $monthlyReceipts = $this->monthlyReceipts($verifiedTransactions, $monthStart, $monthEnd);
        $chartPeriodLabel = $activeAcademicYear?->name ?? 'Tahun ajaran aktif';
        $chartUsesRecordedMonths = false;
        if ($monthlyReceipts->sum('total') <= 0 && $verifiedTotal > 0) {
            $chartUsesRecordedMonths = true;
            $monthlyReceipts = $this->monthlyReceipts($verifiedTransactions, $monthStart, $monthEnd, true);
        }

        $registrationPayments = (clone $verifiedTransactions)
            ->whereHas('tagihan.jenisTagihan', fn ($type) => $type->whereRaw('LOWER(name) LIKE ?', ['%formulir%'])->orWhereRaw('LOWER(name) LIKE ?', ['%pendaftaran%']));
        $reRegistrationPayments = (clone $verifiedTransactions)
            ->whereHas('tagihan.jenisTagihan', fn ($type) => $type->whereRaw('LOWER(name) LIKE ?', ['%daftar ulang%'])->orWhereRaw('LOWER(name) LIKE ?', ['%du%']));
        $registrationVerifiedTotal = (float) (clone $registrationPayments)->sum('amount');
        $reRegistrationVerifiedTotal = (float) (clone $reRegistrationPayments)->sum('amount');
        $registrationPayerCount = (clone $registrationPayments)->join('tagihan_pendaftar', 'transaksi_pembayaran.bill_id', '=', 'tagihan_pendaftar.id')
            ->distinct()->count('tagihan_pendaftar.applicant_id');
        $reRegistrationPayerCount = (clone $reRegistrationPayments)->join('tagihan_pendaftar', 'transaksi_pembayaran.bill_id', '=', 'tagihan_pendaftar.id')
            ->distinct()->count('tagihan_pendaftar.applicant_id');

        return view('bendahara.dashboard.index', [
            'pendingVerificationCount' => TransaksiPembayaran::where('status', 'pending')->count(),
            'verifiedTotal' => $verifiedTotal,
            'todayVerifiedTotal' => (float) (clone $verifiedTransactions)->whereDate('payment_date', today())->sum('amount'),
            'todayTransactionCount' => (clone $verifiedTransactions)->whereDate('payment_date', today())->count(),
            'unpaidBills' => TagihanPendaftar::where('status', 'unpaid')
                ->whereDoesntHave('transaksi', fn ($query) => $query->where('status', 'pending'))
                ->count(),
            'notPaidOffBills' => TagihanPendaftar::whereIn('status', ['unpaid', 'partial'])->count(),
            'targetBills' => $targetBills,
            'activeAcademicYear' => $activeAcademicYear,
            'chartPeriodLabel' => $chartPeriodLabel,
            'chartUsesRecordedMonths' => $chartUsesRecordedMonths,
            'registrationVerifiedTotal' => $registrationVerifiedTotal,
            'reRegistrationVerifiedTotal' => $reRegistrationVerifiedTotal,
            'registrationPayerCount' => $registrationPayerCount,
            'reRegistrationPayerCount' => $reRegistrationPayerCount,
            'monthlyReceipts' => $monthlyReceipts,
            'monthlyReceiptMax' => max(1, (float) $monthlyReceipts->max('total')),
            'registrationApplicantCount' => (clone $registrationBills)->distinct('applicant_id')->count('applicant_id'),
            'reRegistrationApplicantCount' => (clone $reRegistrationBills)->distinct('applicant_id')->count('applicant_id'),
            'reRegistrationInstallmentCount' => (clone $reRegistrationBills)->where('status', 'partial')->distinct('applicant_id')->count('applicant_id'),
            'recentTransactions' => (clone $verifiedTransactions)
                ->with(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user'])
                ->latest('payment_date')->latest('id')->limit(5)->get(),
            'pendingTransactions' => TransaksiPembayaran::query()->where('status', 'pending')
                ->with(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user'])
                ->latest('payment_date')->latest('id')->limit(5)->get(),
        ]);
    }

    private function monthlyReceipts($transactions, Carbon $start, Carbon $end, bool $matchRecordedMonth = false)
    {
        $count = $start->diffInMonths($end) + 1;

        return collect(range(0, $count - 1))->map(function (int $offset) use ($transactions, $start, $matchRecordedMonth) {
            $month = $start->copy()->addMonths($offset);
            $query = clone $transactions;
            $total = $matchRecordedMonth
                ? $query->whereMonth('payment_date', $month->month)->sum('amount')
                : $query->whereBetween('payment_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->sum('amount');

            return [
                'label' => $month->translatedFormat('M'),
                'date' => $month->translatedFormat('F Y'),
                'total' => (float) $total,
            ];
        });
    }

    private function billTypeQuery(array $keywords)
    {
        return TagihanPendaftar::whereHas('jenisTagihan', function ($query) use ($keywords) {
            $query->where(function ($builder) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $builder->orWhere('name', 'like', "%{$keyword}%");
                }
            });
        });
    }
}
