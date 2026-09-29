<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\KunjunganPendaftar;
use App\Models\ReferensiSekolah;
use App\Models\Jurusan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\FullNameNormalizer;
use App\Support\Pagination;
use App\Http\Controllers\Peserta\SekolahAsalController;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LayananPiketController extends Controller
{
    public function index(Request $request)
    {
        $visits = $this->filteredVisitQuery($request)
            ->latest('visited_at')->paginate(Pagination::perPage())->withQueryString();
        $jurusans = Jurusan::where('status', 'aktif')->orderBy('name')->get();
        $visitsToday = KunjunganPendaftar::whereDate('visited_at', today())->count();
        $unregisteredCount = KunjunganPendaftar::whereNull('applicant_id')->count();

        $schools = ReferensiSekolah::query()
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'nonaktif'))
            ->orderBy('nama')->get(['id', 'nama', 'npsn', 'kecamatan', 'kabupaten_kota']);

        return view('panitia.kunjungan.index', compact('visits', 'jurusans', 'schools', 'visitsToday', 'unregisteredCount'));
    }

    public function export(Request $request)
    {
        $visits = $this->filteredVisitQuery($request)->with('referensiSekolah')->latest('visited_at')->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kunjungan Siswa');
        $headers = ['No.', 'Nama calon siswa', 'No. WhatsApp siswa', 'Nama orang tua/wali', 'No. HP orang tua/wali', 'Sekolah asal', 'NPSN', 'Kecamatan', 'Kabupaten/Kota', 'Minat jurusan', 'Tujuan kedatangan', 'Tanggal kunjungan', 'Waktu', 'Petugas penerima', 'Status pendaftaran', 'Catatan'];
        $sheet->fromArray([$headers], null, 'A1');
        $purposeLabels = ['information' => 'Bertanya', 'plan_to_register' => 'Rencana daftar', 'direct_registration' => 'Langsung daftar'];

        $row = 2;
        foreach ($visits as $index => $visit) {
            $school = $visit->referensiSekolah;
            $sheet->fromArray([[$index + 1, $visit->full_name, $visit->visitor_phone, $visit->parent_name, $visit->parent_phone, $visit->origin_school, $visit->origin_school_npsn, $school?->kecamatan, $school?->kabupaten_kota, $visit->major_interest, $purposeLabels[$visit->visit_purpose] ?? $visit->visit_purpose, $visit->visited_at?->format('d-m-Y'), ($visit->visited_at?->format('H:i') ?? '').' WIB', $visit->penerima?->name, $visit->pendaftar ? 'Terhubung / terdaftar' : 'Belum daftar', $visit->notes]], null, 'A'.$row);
            $sheet->setCellValueExplicit('C'.$row, (string) $visit->visitor_phone, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E'.$row, (string) $visit->parent_phone, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G'.$row, (string) $visit->origin_school_npsn, DataType::TYPE_STRING);
            $row++;
        }

        $lastRow = max($row - 1, 1);
        $sheet->getStyle('A1:P1')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '173B78']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]]);
        $sheet->getStyle('A1:P'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        if ($lastRow >= 2) {
            $sheet->getStyle('A2:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:P'.$lastRow);
        foreach ([5, 26, 21, 24, 21, 32, 14, 18, 20, 28, 22, 17, 12, 24, 22, 36] as $index => $width) {
            $sheet->getColumnDimension(chr(65 + $index))->setWidth($width);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
        }, 'kunjungan-calon-siswa-'.now()->format('Ymd-His').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function searchSchool(Request $request)
    {
        $request->merge(['junior_high_only' => true]);

        return app(SekolahAsalController::class)->search($request);
    }

    public function store(Request $request)
    {
        $validated = $this->visitData($request);
        if (KunjunganPendaftar::where('visitor_phone', $validated['visitor_phone'])
            ->whereDate('visited_at', today())->exists()) {
            return back()->withInput()->withErrors(['visitor_phone' => 'Kunjungan dengan nomor WhatsApp ini sudah tercatat hari ini. Periksa daftar kunjungan untuk mengedit data yang ada.']);
        }

        KunjunganPendaftar::create($validated + [
            'applicant_id' => null,
            'visited_at' => now(),
            'received_by' => Auth::id(),
        ]);

        return back()->with('success', 'Data calon siswa berhasil dicatat. Guru penerima: '.Auth::user()->name.'. Siswa dapat melakukan registrasi biasa dari perangkatnya.');
    }

    public function update(Request $request, KunjunganPendaftar $kunjungan)
    {
        $kunjungan->update($this->visitData($request));

        return back()->with('success', 'Data kunjungan '.$kunjungan->full_name.' berhasil diperbarui.');
    }

    public function destroy(KunjunganPendaftar $kunjungan)
    {
        if ($kunjungan->applicant_id) {
            return back()->with('warning', 'Kunjungan yang sudah terhubung ke siswa tidak dapat dihapus agar data pendaftaran dan pembayaran tetap aman.');
        }

        $kunjungan->delete();

        return back()->with('success', 'Data kunjungan berhasil dihapus.');
    }

    private function visitData(Request $request): array
    {
        $validated = $request->validate([
            'visit_purpose' => ['required', 'in:information,plan_to_register,direct_registration'],
            'full_name' => ['required', 'string', 'max:150'],
            'visitor_phone' => ['required', 'string', 'regex:/^08[0-9]{8,13}$/'],
            'parent_name' => ['nullable', 'string', 'max:150', 'required_without:parent_phone'],
            'parent_phone' => ['nullable', 'string', 'regex:/^08[0-9]{8,13}$/', 'required_without:parent_name'],
            'referensi_sekolah_id' => ['required', 'exists:referensi_sekolah,id'],
            'interested_major_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('jurusan', 'id')->where('status', 'aktif'),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $reference = ReferensiSekolah::findOrFail($validated['referensi_sekolah_id']);

        $validated['school_reference_id'] = $reference?->id;
        $validated['visitor_phone'] = $this->normalizePhone($validated['visitor_phone']);
        $validated['parent_phone'] = filled($validated['parent_phone'] ?? null)
            ? $this->normalizePhone($validated['parent_phone'])
            : null;
        $validated['normalized_full_name'] = FullNameNormalizer::normalize($validated['full_name']);
        $validated['origin_school'] = $reference->nama;
        $validated['origin_school_npsn'] = $reference->npsn;
        $major = Jurusan::where('status', 'aktif')->findOrFail($validated['interested_major_id']);
        $validated['major_interest'] = $major->name;
        unset($validated['referensi_sekolah_id']);

        return $validated;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        return str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
    }

    private function filteredVisitQuery(Request $request)
    {
        return KunjunganPendaftar::with(['penerima.role', 'pendaftar'])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('full_name', 'like', '%'.$request->search.'%')
                ->orWhere('visitor_phone', 'like', '%'.$request->search.'%')
                ->orWhere('parent_phone', 'like', '%'.$request->search.'%')
                ->orWhere('origin_school', 'like', '%'.$request->search.'%')));
    }
}
