<?php

namespace App\Services;

use App\Models\{Pendaftar, Jurusan, JalurPendaftaran, GelombangPendaftaran, TahunAjaran, MinatPromosi, TagihanPendaftar, TransaksiPembayaran, PesertaTes};
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExecutiveReportService
{
    public const STATUSES = ['draft' => 'Draft', 'submitted' => 'Menunggu verifikasi', 'verified' => 'Terverifikasi', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', 're_registered' => 'Daftar ulang'];

    public function data(Request $request): array
    {
        $filters = $request->validate([
            'academic_year_id' => 'nullable|integer|min:0', 'month' => 'nullable|date_format:Y-m',
            'major_id' => 'nullable|integer|min:0', 'wave_id' => 'nullable|integer|min:0',
            'path_id' => 'nullable|integer|min:0', 'status' => 'nullable|in:'.implode(',', array_keys(self::STATUSES)),
            'school' => 'nullable|string|max:180', 'search' => 'nullable|string|max:100',
        ]);
        $years = TahunAjaran::orderByDesc('start_date')->get();
        $filters['academic_year_id'] = $filters['academic_year_id'] ?? ($years->firstWhere('is_active', true)?->id ?? 0);
        $query = Pendaftar::studentApplicants()->with(['user', 'biodata', 'sekolahAsal', 'tahunAjaran', 'jurusan1', 'jurusan2', 'jalurPendaftaran', 'gelombangPendaftaran', 'hasilSeleksi.major']);
        foreach (['academic_year_id' => 'academic_year_id', 'major_id' => 'major_choice_1', 'wave_id' => 'wave_id', 'path_id' => 'admission_path_id', 'status' => 'registration_status'] as $filter => $column) {
            if (!empty($filters[$filter])) $query->where($column, $filters[$filter]);
        }
        if (!empty($filters['month'])) {
            $start = Carbon::createFromFormat('!Y-m', $filters['month']);
            $query->where('created_at', '>=', $start)->where('created_at', '<', $start->copy()->addMonth());
        }
        if (!empty($filters['school'])) $query->whereHas('sekolahAsal', fn ($q) => $q->where('school_name', $filters['school']));
        if (!empty($filters['search'])) {
            $term = '%'.$filters['search'].'%';
            $query->where(fn ($q) => $q->where('registration_number', 'like', $term)->orWhereHas('biodata', fn ($b) => $b->where('full_name', 'like', $term))->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)));
        }
        $applicants = $query->orderByDesc('created_at')->orderByDesc('id')->get();
        $ids = $applicants->pluck('id');
        $bills = TagihanPendaftar::whereIn('applicant_id', $ids)->get();
        $payments = TransaksiPembayaran::with(['tagihan.jenisTagihan', 'verifier', 'treasurerReceiver'])->whereIn('bill_id', $bills->pluck('id'))->get();
        $paid = $payments->where('status', 'verified')->groupBy(fn ($p) => $p->tagihan?->applicant_id)->map->sum('amount');
        $targets = $bills->groupBy('applicant_id')->map->sum('total_amount');
        $rows = $applicants->map(fn ($p) => [
            'id' => $p->id, 'number' => $p->registration_number, 'name' => $p->biodata?->full_name ?: $p->user?->name ?: 'Nama belum diisi',
            'phone' => $p->user?->phone ?? '', 'year' => $p->tahunAjaran?->name ?? 'Belum ditentukan',
            'date' => $p->created_at, 'month' => $p->created_at?->format('Y-m') ?? 'Tidak diketahui',
            'major' => $p->jurusan1?->name ?? 'Belum memilih jurusan', 'major2' => $p->jurusan2?->name ?? '',
            'acceptedMajor' => in_array($p->registration_status, ['accepted', 're_registered']) ? ($p->hasilSeleksi?->major?->name ?? 'Belum ditentukan') : '',
            'wave' => $p->gelombangPendaftaran?->name ?? 'Tanpa gelombang',
            'waveQuota' => (int) ($p->gelombangPendaftaran?->quota ?? 0), 'majorQuota' => (int) ($p->jurusan1?->quota ?? 0),
            'path' => $p->jalurPendaftaran?->name ?? 'Belum memilih jalur',
            'school' => trim($p->sekolahAsal?->school_name ?? '') ?: 'Sekolah belum diisi',
            'status' => $p->registration_status, 'statusLabel' => self::STATUSES[$p->registration_status] ?? $p->registration_status,
            'paid' => (float) ($paid[$p->id] ?? 0), 'target' => (float) ($targets[$p->id] ?? 0),
            'remaining' => max(0, (float) ($targets[$p->id] ?? 0) - (float) ($paid[$p->id] ?? 0)),
        ]);
        $majors = Jurusan::orderBy('name')->get();
        $majorSummary = $majors->map(fn ($m) => (object) ['id' => $m->id, 'name' => $m->name, 'applicants' => $rows->where('major', $m->name)->count(), 'accepted' => $rows->where('acceptedMajor', $m->name)->count(), 're_registered' => $rows->where('acceptedMajor', $m->name)->where('status', 're_registered')->count()]);
        if ($rows->where('major', 'Belum memilih jurusan')->isNotEmpty()) $majorSummary->push((object) ['id' => 0, 'name' => 'Belum memilih jurusan', 'applicants' => $rows->where('major', 'Belum memilih jurusan')->count(), 'accepted' => 0, 're_registered' => 0]);
        if ($rows->where('acceptedMajor', 'Belum ditentukan')->isNotEmpty()) $majorSummary->push((object) ['id' => 0, 'name' => 'Jurusan diterima belum ditentukan', 'applicants' => 0, 'accepted' => $rows->where('acceptedMajor', 'Belum ditentukan')->count(), 're_registered' => $rows->where('acceptedMajor', 'Belum ditentukan')->where('status', 're_registered')->count()]);
        $groups = [];
        foreach (['path', 'wave', 'month', 'school'] as $key) {
            $groups[$key] = $rows->groupBy(fn ($r) => $key === 'school' ? mb_strtoupper(preg_replace('/\s+/', ' ', $r[$key])) : $r[$key])->map(fn ($g) => (object) ['name' => $g->first()[$key], 'applicants' => $g->count(), 'accepted' => $g->whereIn('status', ['accepted', 're_registered'])->count(), 're_registered' => $g->where('status', 're_registered')->count(), 'members' => $g->values()])->values();
        }
        $groups['school'] = $groups['school']->sortByDesc('applicants')->values();
        $groups['month'] = $groups['month']->sortBy('name')->values();
        $waves = $rows->groupBy(fn ($r) => json_encode([$r['wave'], $r['major']]))->map(fn ($g) => (object) [
            'wave_name' => $g->first()['wave'], 'major_name' => $g->first()['major'], 'wave_quota' => $g->first()['waveQuota'], 'major_quota' => $g->first()['majorQuota'],
            'applicants' => $g->count(), 'accepted' => $g->whereIn('status', ['accepted', 're_registered'])->count(), 're_registered' => $g->where('status', 're_registered')->count(), 'verified_payment' => $g->sum('paid'),
        ])->values();
        $interestQuery = MinatPromosi::with('jurusanDiminati');
        if (!empty($filters['month'])) $interestQuery->where('submitted_at', '>=', $start)->where('submitted_at', '<', $start->copy()->addMonth());
        if (!empty($filters['major_id'])) $interestQuery->where('interested_major_id', $filters['major_id']);
        $interests = $interestQuery->orderByDesc('submitted_at')->get();
        $promotionSummary = $interests->groupBy(fn ($i) => $i->jurusanDiminati?->name ?? $i->major_interest ?? 'Belum memilih jurusan')->map(fn ($g, $name) => (object) ['name' => $name, 'total' => $g->count(), 'members' => $g])->sortByDesc('total')->values();
        $testSummary = PesertaTes::with('tes')->whereIn('applicant_id', $ids)->get()->groupBy('test_id')->map(function ($g) {
            $attended = $g->where('attendance', 1);
            return (object) ['test_name' => $g->first()->tes?->test_name ?? 'Tes tanpa nama', 'participants' => $attended->count(), 'average_score' => $attended->whereNotNull('score')->avg('score')];
        })->values();
        $verifiedRevenue = (float) $payments->where('status', 'verified')->sum('amount');
        $targetRevenue = (float) $bills->sum('total_amount');
        return [
            'generatedAt' => now(), 'filters' => $filters, 'years' => $years, 'majors' => $majors, 'paths' => JalurPendaftaran::orderBy('name')->get(), 'waves' => GelombangPendaftaran::orderBy('start_date')->get(),
            'yearLabel' => $years->firstWhere('id', $filters['academic_year_id'])?->name ?? 'Semua tahun ajaran',
            'rows' => $rows, 'applicants' => $applicants, 'payments' => $payments, 'interests' => $interests,
            'totalApplicants' => $rows->count(), 'accepted' => $rows->whereIn('status', ['accepted', 're_registered'])->count(), 'reRegistered' => $rows->where('status', 're_registered')->count(), 'pendingVerification' => $rows->where('status', 'submitted')->count(),
            'statusCounts' => $rows->countBy('status'), 'majorSummary' => $majorSummary->sortByDesc('applicants')->values(), 'waveMajorSummary' => $waves,
            'pathSummary' => $groups['path'], 'waveSummary' => $groups['wave'], 'monthSummary' => $groups['month'], 'schoolSummary' => $groups['school'], 'testSummary' => $testSummary,
            'promotionSummary' => $promotionSummary, 'scholarshipRows' => $rows->filter(fn ($r) => str_contains(mb_strtolower($r['path']), 'beasiswa'))->values(),
            'verifiedRevenue' => $verifiedRevenue, 'targetRevenue' => $targetRevenue, 'financeProgress' => $targetRevenue > 0 ? min(100, (int) round($verifiedRevenue / $targetRevenue * 100)) : 0, 'chartMaxApplicants' => max(1, (int) $majorSummary->max('applicants')),
        ];
    }
}
