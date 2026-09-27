<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\SystemSetting;
use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use App\Support\Pagination;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        return view('bendahara.laporan.index', $this->reportData($request, true));
    }

    public function print(Request $request): View
    {
        return view('bendahara.laporan.print', $this->officialReportData($request));
    }

    public function pdf(Request $request)
    {
        return Pdf::loadView('bendahara.laporan.print', $this->officialReportData($request, false))
            ->setPaper('a4', 'portrait')
            ->download('laporan-keuangan-spmb-'.now()->format('Ymd-His').'.pdf');
    }

    private function reportData(Request $request, bool $paginate): array
    {
        $filters = $this->filters($request);
        $baseQuery = $this->reportTransactions($filters);
        $transactions = $paginate
            ? (clone $baseQuery)->paginate(Pagination::perPage())->withQueryString()
            : (clone $baseQuery)->get();
        $summaryByBillType = $this->applyFilters(TransaksiPembayaran::query()
            ->selectRaw('jenis_tagihan.name as bill_type_name, COUNT(transaksi_pembayaran.id) as transaction_count, SUM(transaksi_pembayaran.amount) as total_amount')
            ->join('tagihan_pendaftar', 'transaksi_pembayaran.bill_id', '=', 'tagihan_pendaftar.id')
            ->join('jenis_tagihan', 'tagihan_pendaftar.bill_type_id', '=', 'jenis_tagihan.id')
            ->leftJoin('pendaftar', 'tagihan_pendaftar.applicant_id', '=', 'pendaftar.id')
            ->where('transaksi_pembayaran.status', 'verified'), $filters)
            ->groupBy('jenis_tagihan.name')
            ->orderBy('jenis_tagihan.name')
            ->get();

        $periodStart = $filters['start_date'] ?? (clone $baseQuery)->min('payment_date');
        $periodEnd = $filters['end_date'] ?? (clone $baseQuery)->max('payment_date');
        $periodLabel = $periodStart && $periodEnd
            ? \Illuminate\Support\Carbon::parse($periodStart)->translatedFormat('d F Y') . ' s/d ' . \Illuminate\Support\Carbon::parse($periodEnd)->translatedFormat('d F Y')
            : 'Belum ada transaksi pada periode ini';

        return [
            'transactions' => $transactions,
            'periodLabel' => $periodLabel,
            'summaryByBillType' => $summaryByBillType,
            'students' => Pendaftar::with(['biodata', 'user'])->latest('id')->get(),
            'filters' => $filters,
            'verifiedTotal' => (clone $baseQuery)->sum('amount'),
            'registrationIncome' => $this->reportTransactions($filters)
                ->whereHas('tagihan.jenisTagihan', fn ($query) => $query->where('name', 'like', '%formulir%'))
                ->sum('amount'),
            'reRegistrationIncome' => $this->reportTransactions($filters)
                ->whereHas('tagihan.jenisTagihan', fn ($query) => $query->where('name', 'like', '%daftar%'))
                ->sum('amount'),
            'targetBills' => $this->applyBillFilters(TagihanPendaftar::query(), $filters)->sum('total_amount'),
        ];
    }

    private function officialReportData(Request $request, bool $withPrintAction = true): array
    {
        $data = $this->reportData($request, false);
        $settings = SystemSetting::publicValues();
        $letterheadPath = $settings['letterhead_path'] ?? null;
        $letterheadFile = $letterheadPath ? public_path($letterheadPath) : null;
        $data['settings'] = $settings;
        $data['letterheadSrc'] = $letterheadFile && is_file($letterheadFile)
            ? 'data:image/'.pathinfo($letterheadFile, PATHINFO_EXTENSION).';base64,'.base64_encode((string) file_get_contents($letterheadFile))
            : null;
        $data['withPrintAction'] = $withPrintAction;
        return $data;
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $fileName = 'laporan-keuangan-spmb-' . now()->format('Ymd-His') . '.xls';

        return response()->streamDownload(function () use ($filters) {
            echo '<html><head><meta charset="UTF-8"><style>';
            echo 'table{border-collapse:collapse;width:100%;font-family:Arial,sans-serif;font-size:12px}';
            echo 'th{background:#0f766e;color:#fff;text-align:left}th,td{border:1px solid #cbd5e1;padding:8px}';
            echo '.right{text-align:right}.title{font-size:18px;font-weight:bold;margin-bottom:4px}.meta{font-size:12px;margin-bottom:14px;color:#475569}';
            echo '</style></head><body>';
            echo '<div class="title">Laporan Keuangan SPMB</div>';
            echo '<div class="meta">SMK Muhammadiyah 4 Cileungsi - Dicetak '.e(now()->format('d/m/Y H:i')).'</div>';
            echo '<table><thead><tr>';
            foreach (['Tanggal', 'No Transaksi', 'Nama Peserta', 'No Pendaftaran', 'Jenis Tagihan', 'Metode', 'Referensi', 'Nominal'] as $heading) {
                echo '<th>'.e($heading).'</th>';
            }
            echo '</tr></thead><tbody>';

            $this->reportTransactions($filters)->chunk(100, function ($transactions) {
                foreach ($transactions as $transaction) {
                    echo '<tr>';
                    echo '<td>'.e($transaction->payment_date ? date('d/m/Y', strtotime($transaction->payment_date)) : '-').'</td>';
                    echo '<td>'.e($transaction->transaction_number).'</td>';
                    echo '<td>'.e($transaction->tagihan?->pendaftar?->biodata?->full_name ?? $transaction->tagihan?->pendaftar?->user?->name ?? 'Peserta').'</td>';
                    echo '<td>'.e($transaction->tagihan?->pendaftar?->registration_number ?? '-').'</td>';
                    echo '<td>'.e($transaction->tagihan?->jenisTagihan?->name ?? 'Tagihan SPMB').'</td>';
                    echo '<td>'.e($transaction->payment_method === 'cash' ? 'Tunai' : 'Transfer').'</td>';
                    echo '<td>'.e($transaction->reference_number ?? '-').'</td>';
                    echo '<td class="right">'.e((string) (float) $transaction->amount).'</td>';
                    echo '</tr>';
                }
            });

            echo '</tbody></table></body></html>';
        }, $fileName, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function reportTransactions(array $filters = [])
    {
        return $this->applyFilters(TransaksiPembayaran::with([
                'tagihan.jenisTagihan',
                'tagihan.pendaftar.biodata',
                'tagihan.pendaftar.user',
            ])
            ->where('status', 'verified')
            ->latest('payment_date')
            ->latest('id'), $filters);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'applicant_id' => ['nullable', 'integer', 'exists:pendaftar,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }

    private function applyFilters($query, array $filters)
    {
        return $query
            ->when($filters['applicant_id'] ?? null, function ($inner, $applicantId) {
                $inner->whereHas('tagihan', fn ($bill) => $bill->where('applicant_id', $applicantId));
            })
            ->when($filters['start_date'] ?? null, fn ($inner, $date) => $inner->whereDate('payment_date', '>=', $date))
            ->when($filters['end_date'] ?? null, fn ($inner, $date) => $inner->whereDate('payment_date', '<=', $date));
    }

    private function applyBillFilters($query, array $filters)
    {
        return $query
            ->when($filters['applicant_id'] ?? null, fn ($inner, $applicantId) => $inner->where('applicant_id', $applicantId));
    }
}
