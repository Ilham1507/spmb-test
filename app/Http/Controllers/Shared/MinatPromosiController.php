<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\MinatPromosi;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MinatPromosiController extends Controller
{
    public function create()
    {
        $jurusans = Jurusan::where('status', 'aktif')->orderBy('name')->get(['id', 'name']);

        return view('promosi.minat-form', compact('jurusans'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        if (MinatPromosi::where('student_phone', $data['student_phone'])->exists()) {
            return back()->withInput()->withErrors(['student_phone' => 'Nomor WhatsApp ini sudah tercatat sebagai siswa yang berminat.']);
        }

        MinatPromosi::create($data + ['submitted_at' => now()]);

        $selectedMajor = filled($data['interested_major_id'] ?? null)
            ? Jurusan::find($data['interested_major_id'])
            : null;

        return redirect()->route('promosi.minat.success')->with([
            'selected_major_name' => $selectedMajor?->name,
            'selected_major_url' => $selectedMajor
                ? route('konsentrasi.show', $selectedMajor)
                : route('jurusan'),
        ]);
    }

    public function success()
    {
        return view('promosi.minat-success');
    }

    public function index(Request $request)
    {
        $interests = $this->filteredQuery($request)->latest('submitted_at')->paginate(Pagination::perPage())->withQueryString();

        return view('promosi.minat-index', compact('interests'));
    }

    public function export(Request $request)
    {
        $interests = $this->filteredQuery($request)->latest('submitted_at')->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Hasil Promosi');
        $headers = ['No.', 'Nama siswa', 'No. WhatsApp siswa', 'Media sosial', 'SMP/MTs saat ini', 'Jurusan diminati', 'Diisi pada'];
        $sheet->fromArray([$headers], null, 'A1');

        $row = 2;
        foreach ($interests as $index => $interest) {
            $sheet->fromArray([[$index + 1, $interest->full_name, $interest->student_phone, $interest->social_media, $interest->school_name, $interest->major_interest, $interest->submitted_at?->format('d-m-Y H:i')]], null, 'A'.$row);
            $sheet->setCellValueExplicit('C'.$row, (string) $interest->student_phone, DataType::TYPE_STRING);
            $row++;
        }

        $lastRow = max($row - 1, 1);
        $sheet->getStyle('A1:G1')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]]);
        $sheet->getStyle('A1:G'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:G'.$lastRow);
        foreach ([5, 28, 21, 24, 32, 28, 20] as $index => $width) {
            $sheet->getColumnDimension(chr(65 + $index))->setWidth($width);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
        }, 'hasil-promosi-siswa-'.now()->format('Ymd-His').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function destroy(MinatPromosi $minatPromosi)
    {
        $minatPromosi->delete();

        return back()->with('success', 'Data hasil promosi berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'student_phone' => ['required', 'string', 'regex:/^08[0-9]{8,13}$/'],
            'social_media' => ['required', 'string', 'max:150'],
            'school_name' => ['required', 'string', 'max:180'],
            'interested_major_id' => ['required', Rule::exists('jurusan', 'id')->where('status', 'aktif')],
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'full_name.max' => 'Nama lengkap terlalu panjang.',
            'student_phone.required' => 'Nomor WhatsApp wajib diisi.',
            'student_phone.regex' => 'Masukkan nomor WhatsApp yang benar, misalnya 08xxxxxxxxxx.',
            'social_media.required' => 'Media sosial wajib diisi.',
            'social_media.max' => 'Media sosial terlalu panjang.',
            'school_name.required' => 'Nama SMP/MTs wajib diisi.',
            'school_name.max' => 'Nama SMP/MTs terlalu panjang.',
            'interested_major_id.required' => 'Pilih jurusan yang kamu minati.',
            'interested_major_id.exists' => 'Jurusan yang dipilih tidak tersedia. Silakan pilih lagi.',
        ]);
        $data['full_name'] = Str::title(Str::lower(trim(preg_replace('/\s+/', ' ', $data['full_name']) ?? '')));
        $data['student_phone'] = $this->normalizePhone($data['student_phone']);
        $data['major_interest'] = filled($data['interested_major_id'] ?? null)
            ? Jurusan::find($data['interested_major_id'])?->name
            : null;

        return $data;
    }

    private function filteredQuery(Request $request)
    {
        return MinatPromosi::query()->when($request->filled('search'), fn ($query) => $query->where(fn ($inner) => $inner
            ->where('full_name', 'like', '%'.$request->search.'%')
            ->orWhere('student_phone', 'like', '%'.$request->search.'%')
            ->orWhere('social_media', 'like', '%'.$request->search.'%')
            ->orWhere('school_name', 'like', '%'.$request->search.'%')
            ->orWhere('major_interest', 'like', '%'.$request->search.'%')));
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
    }
}
