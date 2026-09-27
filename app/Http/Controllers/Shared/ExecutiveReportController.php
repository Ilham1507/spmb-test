<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\JalurPendaftaran;
use App\Models\Jurusan;
use App\Models\GelombangPendaftaran;
use App\Models\Pendaftar;
use App\Models\PesertaTes;
use App\Models\TagihanPendaftar;
use App\Models\TesMasuk;
use App\Models\TransaksiPembayaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecutiveReportController extends Controller
{
    public function index(Request $request)
    {
        return view('shared.executive-report.index', $this->reportData());
    }

    public function pdf(Request $request)
    {
        return Pdf::loadView('shared.executive-report.print', $this->reportData())
            ->setPaper('a4', 'portrait')
            ->download('laporan-eksekutif-spmb-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $data = $this->reportData();

        return response()->streamDownload(function () use ($data) {
            echo '<html><head><meta charset="UTF-8"><style>';
            echo 'body{font-family:Arial,sans-serif;color:#172033}h1{font-size:18px}h2{font-size:14px;margin-top:24px}table{border-collapse:collapse;width:100%;font-size:11px}th{background:#0f766e;color:#fff;text-align:left}th,td{border:1px solid #cbd5e1;padding:7px}.right{text-align:right}';
            echo '</style></head><body><h1>Laporan Eksekutif SPMB</h1><p>Dicetak '.e(now()->format('d/m/Y H:i')).'</p>';
            echo '<h2>Ringkasan</h2><table><tr><th>Total pendaftar</th><th>Diterima</th><th>Daftar ulang</th><th>Dana terverifikasi</th><th>Target tagihan</th></tr><tr>';
            echo '<td>'.e($data['totalApplicants']).'</td><td>'.e($data['accepted']).'</td><td>'.e($data['reRegistered']).'</td><td class="right">'.e((string) $data['verifiedRevenue']).'</td><td class="right">'.e((string) $data['targetRevenue']).'</td></tr></table>';
            echo '<h2>Rekap per jurusan</h2><table><tr><th>Jurusan</th><th>Pendaftar</th><th>Diterima</th><th>Daftar ulang</th></tr>';
            foreach ($data['majorSummary'] as $row) {
                echo '<tr><td>'.e($row->name).'</td><td>'.e($row->applicants).'</td><td>'.e($row->accepted).'</td><td>'.e($row->re_registered).'</td></tr>';
            }
            echo '</table><h2>Rekap per gelombang dan jurusan</h2><table><tr><th>Gelombang</th><th>Jurusan</th><th>Kuota gelombang</th><th>Kuota jurusan</th><th>Pendaftar</th><th>Diterima</th><th>Daftar ulang</th><th>Pembayaran terverifikasi</th></tr>';
            foreach ($data['waveMajorSummary'] as $row) {
                echo '<tr><td>'.e($row->wave_name).'</td><td>'.e($row->major_name).'</td><td>'.e($row->wave_quota).'</td><td>'.e($row->major_quota).'</td><td>'.e($row->applicants).'</td><td>'.e($row->accepted).'</td><td>'.e($row->re_registered).'</td><td class="right">'.e(number_format((float) $row->verified_payment, 0, ',', '.')).'</td></tr>';
            }
            echo '</table><h2>Rekap nilai tes</h2><table><tr><th>Tes</th><th>Peserta hadir</th><th>Rata-rata nilai</th></tr>';
            foreach ($data['testSummary'] as $row) {
                echo '<tr><td>'.e($row->test_name).'</td><td>'.e($row->participants).'</td><td>'.e($row->average_score !== null ? number_format((float) $row->average_score, 2, ',', '.') : '-').'</td></tr>';
            }
            echo '</table></body></html>';
        }, 'laporan-eksekutif-spmb-'.now()->format('Ymd-His').'.xls', ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    private function reportData(): array
    {
        $statusCounts = Pendaftar::query()
            ->selectRaw('registration_status, COUNT(*) as total')
            ->groupBy('registration_status')
            ->pluck('total', 'registration_status');

        $majorSummary = Jurusan::query()
            ->leftJoin('pendaftar', 'jurusan.id', '=', 'pendaftar.major_choice_1')
            ->selectRaw("jurusan.id, jurusan.name, COUNT(pendaftar.id) as applicants, SUM(CASE WHEN pendaftar.registration_status = 'accepted' THEN 1 ELSE 0 END) as accepted, SUM(CASE WHEN pendaftar.registration_status = 're_registered' THEN 1 ELSE 0 END) as re_registered")
            ->groupBy('jurusan.id', 'jurusan.name')
            ->orderByDesc('applicants')
            ->get();

        $paymentByApplicant = TagihanPendaftar::query()
            ->leftJoin('transaksi_pembayaran as pembayaran', function ($join) {
                $join->on('tagihan_pendaftar.id', '=', 'pembayaran.bill_id')
                    ->where('pembayaran.status', '=', 'verified');
            })
            ->selectRaw('tagihan_pendaftar.applicant_id, COALESCE(SUM(pembayaran.amount), 0) as verified_payment')
            ->groupBy('tagihan_pendaftar.applicant_id');

        $waveMajorSummary = Pendaftar::query()
            ->leftJoin('gelombang_pendaftaran', 'pendaftar.wave_id', '=', 'gelombang_pendaftaran.id')
            ->leftJoin('jurusan', 'pendaftar.major_choice_1', '=', 'jurusan.id')
            ->leftJoinSub($paymentByApplicant, 'payment_summary', function ($join) {
                $join->on('pendaftar.id', '=', 'payment_summary.applicant_id');
            })
            ->selectRaw("COALESCE(gelombang_pendaftaran.name, 'Tanpa gelombang') as wave_name, COALESCE(gelombang_pendaftaran.quota, 0) as wave_quota, COALESCE(jurusan.name, 'Belum memilih jurusan') as major_name, COALESCE(jurusan.quota, 0) as major_quota, COUNT(pendaftar.id) as applicants, SUM(CASE WHEN pendaftar.registration_status = 'accepted' THEN 1 ELSE 0 END) as accepted, SUM(CASE WHEN pendaftar.registration_status = 're_registered' THEN 1 ELSE 0 END) as re_registered, COALESCE(SUM(payment_summary.verified_payment), 0) as verified_payment")
            ->groupBy('gelombang_pendaftaran.id', 'gelombang_pendaftaran.name', 'gelombang_pendaftaran.quota', 'jurusan.id', 'jurusan.name', 'jurusan.quota')
            ->orderBy('gelombang_pendaftaran.start_date')
            ->orderBy('jurusan.name')
            ->get();

        $pathSummary = JalurPendaftaran::query()
            ->leftJoin('pendaftar', 'jalur_pendaftaran.id', '=', 'pendaftar.admission_path_id')
            ->selectRaw('jalur_pendaftaran.id, jalur_pendaftaran.name, COUNT(pendaftar.id) as applicants')
            ->groupBy('jalur_pendaftaran.id', 'jalur_pendaftaran.name')
            ->orderByDesc('applicants')
            ->get();

        $testSummary = TesMasuk::query()
            ->leftJoin('peserta_tes', 'tes_masuk.id', '=', 'peserta_tes.test_id')
            ->selectRaw('tes_masuk.id, tes_masuk.test_name, SUM(CASE WHEN peserta_tes.attendance = 1 THEN 1 ELSE 0 END) as participants, AVG(CASE WHEN peserta_tes.attendance = 1 THEN peserta_tes.score ELSE NULL END) as average_score')
            ->groupBy('tes_masuk.id', 'tes_masuk.test_name')
            ->orderBy('tes_masuk.test_name')
            ->get();

        $verifiedRevenue = (float) TransaksiPembayaran::where('status', 'verified')->sum('amount');
        $targetRevenue = (float) TagihanPendaftar::sum('total_amount');

        return [
            'generatedAt' => now(),
            'totalApplicants' => Pendaftar::count(),
            'accepted' => (int) ($statusCounts['accepted'] ?? 0),
            'reRegistered' => (int) ($statusCounts['re_registered'] ?? 0),
            'pendingVerification' => (int) ($statusCounts['submitted'] ?? 0),
            'statusCounts' => $statusCounts,
            'majorSummary' => $majorSummary,
            'waveMajorSummary' => $waveMajorSummary,
            'pathSummary' => $pathSummary,
            'testSummary' => $testSummary,
            'verifiedRevenue' => $verifiedRevenue,
            'targetRevenue' => $targetRevenue,
            'financeProgress' => $targetRevenue > 0 ? min(100, (int) round(($verifiedRevenue / $targetRevenue) * 100)) : 0,
            'chartMaxApplicants' => max(1, (int) $majorSummary->max('applicants')),
        ];
    }
}
