<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DokumenPendaftar;
use App\Models\AuditLog;
use App\Models\GelombangPendaftaran;
use App\Models\Jurusan;
use App\Models\KunjunganPendaftar;
use App\Models\Pendaftar;
use App\Models\PesertaTes;
use App\Models\TagihanPendaftar;
use App\Models\TransaksiPembayaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Support\Pagination;

class DashboardController extends Controller
{
    public function index()
    {
        $studentIds = Pendaftar::studentApplicants()->select('pendaftar.id');
        $statusCounts = Pendaftar::studentApplicants()
            ->select('registration_status', DB::raw('count(*) as total'))
            ->groupBy('registration_status')
            ->pluck('total', 'registration_status');

        $paymentCounts = TagihanPendaftar::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $finance = [
            'target' => (float) TagihanPendaftar::sum('total_amount'),
            'verified' => (float) TransaksiPembayaran::where('status', 'verified')->sum('amount'),
            'waiting_check' => TransaksiPembayaran::where('status', 'pending')->count(),
            'unpaid' => TagihanPendaftar::where('status', 'unpaid')
                ->whereDoesntHave('transaksi', fn ($query) => $query->where('status', 'pending'))
                ->count(),
            'partial_du' => TagihanPendaftar::where('status', 'partial')
                ->whereHas('jenisTagihan', fn ($query) => $query->where('name', 'like', '%daftar ulang%')->orWhere('name', 'like', '%DU%'))
                ->count(),
        ];

        $statusChart = collect([
            ['label' => 'Draft', 'value' => (int) ($statusCounts['draft'] ?? 0), 'color' => 'bg-slate-400'],
            ['label' => 'Menunggu', 'value' => (int) ($statusCounts['submitted'] ?? 0), 'color' => 'bg-amber-400'],
            ['label' => 'Terverifikasi', 'value' => (int) ($statusCounts['verified'] ?? 0), 'color' => 'bg-sky-500'],
            ['label' => 'Diterima', 'value' => (int) ($statusCounts['accepted'] ?? 0), 'color' => 'bg-emerald-500'],
            ['label' => 'Daftar Ulang', 'value' => (int) ($statusCounts['re_registered'] ?? 0), 'color' => 'bg-teal-600'],
            ['label' => 'Ditolak', 'value' => (int) ($statusCounts['rejected'] ?? 0), 'color' => 'bg-rose-500'],
        ]);

        $paymentChart = collect([
            ['label' => 'Belum bayar', 'value' => (int) ($paymentCounts['unpaid'] ?? 0), 'color' => 'bg-rose-400'],
            ['label' => 'Menunggu cek', 'value' => $finance['waiting_check'], 'color' => 'bg-amber-400'],
            ['label' => 'Cicil DU', 'value' => $finance['partial_du'], 'color' => 'bg-orange-400'],
            ['label' => 'Lunas', 'value' => (int) ($paymentCounts['paid'] ?? 0), 'color' => 'bg-emerald-500'],
        ]);

        $financeByType = TransaksiPembayaran::query()
            ->join('tagihan_pendaftar', 'transaksi_pembayaran.bill_id', '=', 'tagihan_pendaftar.id')
            ->join('jenis_tagihan', 'tagihan_pendaftar.bill_type_id', '=', 'jenis_tagihan.id')
            ->where('transaksi_pembayaran.status', 'verified')
            ->select('jenis_tagihan.name as label', DB::raw('sum(transaksi_pembayaran.amount) as total'))
            ->groupBy('jenis_tagihan.id', 'jenis_tagihan.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($item) => ['label' => $item->label, 'value' => (float) $item->total]);

        $majorChart = Jurusan::query()
            ->leftJoinSub(Pendaftar::studentApplicants()->select('pendaftar.*'), 'pendaftar', 'jurusan.id', '=', 'pendaftar.major_choice_1')
            ->select('jurusan.name', DB::raw('count(pendaftar.id) as total'))
            ->groupBy('jurusan.id', 'jurusan.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($major) => ['label' => $major->name, 'value' => (int) $major->total]);

        $monthlyApplicants = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = Carbon::today()->subMonthsNoOverflow($monthsAgo);
            $monthNames = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            return [
                'label' => $monthNames[(int) $date->format('n')],
                'value' => Pendaftar::studentApplicants()->whereBetween('created_at', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])->count(),
            ];
        });

        $adminActivityChart = collect(range(11, 0))->map(function ($monthsAgo) {
            $date = Carbon::today()->subMonthsNoOverflow($monthsAgo);
            $monthNames = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            return [
                'label' => $monthNames[(int) $date->format('n')],
                'year' => $date->format('Y'),
                'applicants' => Pendaftar::studentApplicants()->whereBetween('created_at', [$start, $end])->count(),
                'payments' => TransaksiPembayaran::where('status', 'verified')->whereBetween('payment_date', [$start, $end])->count(),
            ];
        });

        $dataHealth = [
            ['label' => 'NISN valid', 'value' => DB::table('biodata_pendaftar')->whereIn('applicant_id', $studentIds)->whereRaw('CHAR_LENGTH(nisn) = 10')->count()],
            ['label' => 'NIK terisi', 'value' => DB::table('biodata_pendaftar')->whereIn('applicant_id', $studentIds)->whereRaw('CHAR_LENGTH(nik) = 16')->count()],
            ['label' => 'Jarak sekolah terisi', 'value' => DB::table('alamat_pendaftar')->whereIn('applicant_id', $studentIds)->whereNotNull('distance_range')->count()],
            ['label' => 'Sekolah asal lengkap', 'value' => DB::table('sekolah_asal')->whereIn('applicant_id', $studentIds)->whereNotNull('school_name')->count()],
        ];

        $latestApplicants = Pendaftar::studentApplicants()->with(['biodata', 'jurusan1'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        return view('admin.dashboard.index', [
            'totalPendaftar' => Pendaftar::studentApplicants()->count(),
            'draft' => (int) ($statusCounts['draft'] ?? 0),
            'submitted' => (int) ($statusCounts['submitted'] ?? 0),
            'verified' => (int) ($statusCounts['verified'] ?? 0),
            'accepted' => (int) ($statusCounts['accepted'] ?? 0),
            'reRegistered' => (int) ($statusCounts['re_registered'] ?? 0),
            'rejected' => (int) ($statusCounts['rejected'] ?? 0),
            'jurusanAktif' => Jurusan::where('status', 'aktif')->count(),
            'gelombangAktif' => GelombangPendaftaran::where('status', 'aktif')->first(),
            'totalPengguna' => User::count(),
            'statusChart' => $statusChart,
            'paymentChart' => $paymentChart,
            'financeByType' => $financeByType,
            'majorChart' => $majorChart,
            'monthlyApplicants' => $monthlyApplicants,
            'adminActivityChart' => $adminActivityChart,
            'finance' => $finance,
            'documentUploaded' => DokumenPendaftar::whereIn('applicant_id', $studentIds)->whereNotNull('file_path')->count(),
            'documentPending' => DokumenPendaftar::whereIn('applicant_id', $studentIds)->whereIn('status', ['uploaded', 'pending'])->count(),
            'testAttendance' => PesertaTes::whereIn('applicant_id', $studentIds)->where('attendance', true)->distinct('applicant_id')->count('applicant_id'),
            'visitsToday' => KunjunganPendaftar::whereDate('visited_at', today())->count(),
            'dataHealth' => $dataHealth,
            'latestApplicants' => $latestApplicants,
        ]);
    }

    public function activityLog(Request $request)
    {
        $auditLogs = AuditLog::with('actor')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);
                $query->where(function ($logs) use ($search) {
                    $logs->where('subject_label', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhereHas('actor', fn ($actor) => $actor->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(Pagination::perPage())
            ->withQueryString();

        return view('admin.activity-log.index', compact('auditLogs'));
    }

    private function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($digits) <= 6) {
            return $digits ?: '-';
        }

        return substr($digits, 0, 4) . str_repeat('*', max(3, strlen($digits) - 7)) . substr($digits, -3);
    }

    private function registrationStatusLabel(?string $status): string
    {
        return [
            'draft' => 'Draft',
            'submitted' => 'Menunggu verifikasi',
            'verified' => 'Terverifikasi',
            'accepted' => 'Diterima',
            're_registered' => 'Daftar ulang',
            'rejected' => 'Ditolak',
        ][$status] ?? 'Belum ada status';
    }

    private function paymentStatusLabel(?string $status): string
    {
        return [
            'pending' => 'Menunggu cek',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
        ][$status] ?? 'Belum lunas';
    }

    private function documentStatusLabel(?string $status): string
    {
        return [
            'uploaded' => 'Terunggah',
            'pending' => 'Menunggu cek',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ][$status] ?? 'Belum ada status';
    }
}
