<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\CbtAccessSession;
use App\Models\HasilPemeriksaanKesehatanPendaftar;
use App\Models\HasilUkurSeragamPendaftar;
use App\Models\JawabanCbtPendaftar;
use App\Models\ItemPemeriksaanKesehatan;
use App\Models\Pendaftar;
use App\Models\PertanyaanCbt;
use App\Models\PertanyaanWawancara;
use App\Models\PesertaTes;
use App\Models\TesMasuk;
use App\Models\UkuranSeragam;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TesSpmbController extends Controller
{
    public function btq(Request $request) { return $this->showTest($request, 'Baca Tulis Quran'); }
    public function uniform(Request $request) { return $this->showTest($request, 'Tes Ukuran Seragam'); }
    public function health(Request $request) { return $this->showTest($request, 'Tes Kesehatan'); }
    public function cbt(Request $request)
    {
        $canManageTests = ! $request->routeIs('bendahara.*') && ! auth()->user()?->hasRole('kepala_sekolah');

        if ($request->routeIs('admin.*')) {
            return $this->manageCbt();
        }
        $applicants = Pendaftar::with(['biodata', 'jurusan1'])
            ->whereIn('registration_status', ['verified', 'accepted'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('biodata', fn ($biodata) => $biodata->where('full_name', 'like', "%{$search}%"))
                        ->orWhere('registration_number', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(Pagination::perPage())
            ->withQueryString();

        $activeApplicantIds = CbtAccessSession::where('status', 'open')
            ->whereNull('closed_at')
            ->where('expires_at', '>=', now())
            ->pluck('applicant_id');
        $cbtTest = TesMasuk::where('test_name', 'Tes CBT')->first();
        $completedResults = $cbtTest
            ? PesertaTes::where('test_id', $cbtTest->id)
                ->where('attendance', true)
                ->latest()
                ->get()
                ->keyBy('applicant_id')
            : collect();
        $completedApplicantIds = $completedResults->keys();
        $completedApplicants = $applicants->whereIn('id', $completedApplicantIds)->values();
        $pendingApplicants = $applicants->whereNotIn('id', $completedApplicantIds)->values();
        $completedSessions = $completedApplicantIds->isNotEmpty()
            ? CbtAccessSession::whereIn('applicant_id', $completedApplicantIds)
                ->latest('opened_at')
                ->get()
                ->unique('applicant_id')
                ->keyBy('applicant_id')
            : collect();
        return view('panitia.tes.cbt', compact(
            'applicants',
            'activeApplicantIds',
            'completedApplicants',
            'pendingApplicants',
            'completedResults',
            'completedSessions',
            'canManageTests'
        ));
    }
    public function interview(Request $request)
    {
        return $request->routeIs('admin.*') ? $this->manageInterviewQuestions() : $this->showTest($request, 'Wawancara Orang Tua');
    }

    public function index(Request $request)
    {
        $applicants = Pendaftar::with(['biodata', 'jurusan1'])
            ->whereIn('registration_status', ['verified', 'accepted', 'rejected'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('biodata', function ($biodata) use ($search) {
                        $biodata->where('full_name', 'like', "%{$search}%");
                    })->orWhere('registration_number', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(Pagination::perPage())
            ->withQueryString();

        $activeTest = $request->get('jenis_tes', 'Baca Tulis Quran');

        $canManageTests = ! $request->routeIs('bendahara.*') && ! auth()->user()?->hasRole('kepala_sekolah');
        $selectedApplicant = $canManageTests && $request->pendaftar
            ? $applicants->firstWhere('id', (int) $request->pendaftar)
            : null;

        $tests = TesMasuk::whereIn('test_name', $this->testNames())
            ->orderByRaw("FIELD(test_name, 'Baca Tulis Quran', 'Tes Ukuran Seragam', 'Tes Kesehatan', 'Tes CBT', 'Wawancara Orang Tua')")
            ->get()
            ->keyBy('test_name');

        $activeTestModel = $tests->get($activeTest);
        $completedApplicantIds = $activeTestModel
            ? PesertaTes::where('test_id', $activeTestModel->id)->where('attendance', true)->pluck('applicant_id')
            : collect();
        $completedApplicants = $applicants->whereIn('id', $completedApplicantIds)->values();
        $pendingApplicants = $applicants->whereNotIn('id', $completedApplicantIds)->values();
        $completedTestResults = $activeTestModel
            ? PesertaTes::where('test_id', $activeTestModel->id)
                ->whereIn('applicant_id', $completedApplicantIds)->get()->keyBy('applicant_id')
            : collect();
        $completedUniformResults = $activeTest === 'Tes Ukuran Seragam'
            ? HasilUkurSeragamPendaftar::whereIn('applicant_id', $completedApplicantIds)->get()->keyBy('applicant_id')
            : collect();
        $completedHealthResults = $activeTest === 'Tes Kesehatan'
            ? HasilPemeriksaanKesehatanPendaftar::whereIn('applicant_id', $completedApplicantIds)->get()->groupBy('applicant_id')
            : collect();

        $testResults = collect();
        $uniformResult = null;
        $healthResults = collect();
        $cbtSession = null;
        $cbtAnswerCount = 0;

        if ($selectedApplicant) {
            $testResults = PesertaTes::where('applicant_id', $selectedApplicant->id)
                ->whereIn('test_id', $tests->pluck('id'))
                ->get()
                ->keyBy('test_id');

            $uniformResult = HasilUkurSeragamPendaftar::where('applicant_id', $selectedApplicant->id)->first();

            $healthResults = HasilPemeriksaanKesehatanPendaftar::where('applicant_id', $selectedApplicant->id)
                ->get()
                ->keyBy('health_check_item_id');

            $cbtSession = CbtAccessSession::where('applicant_id', $selectedApplicant->id)
                ->latest()
                ->first();

            $cbtAnswerCount = JawabanCbtPendaftar::where('applicant_id', $selectedApplicant->id)->count();
        }

        $uniformSizes = UkuranSeragam::where('status', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $healthItems = ItemPemeriksaanKesehatan::where('status', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $cbtQuestions = PertanyaanCbt::latest()->paginate(Pagination::perPage(), ['*'], 'cbt_page')->withQueryString();
        $interviewQuestions = $activeTest === 'Wawancara Orang Tua'
            ? PertanyaanWawancara::where('status', true)->orderBy('sort_order')->orderBy('id')->get()
            : collect();

        return view('panitia.tes.index', compact(
            'applicants',
            'selectedApplicant',
            'tests',
            'testResults',
            'uniformResult',
            'healthItems',
            'healthResults',
            'uniformSizes',
            'cbtQuestions',
            'activeTest',
            'cbtSession',
            'cbtAnswerCount'
            , 'completedApplicants', 'pendingApplicants', 'completedTestResults',
            'completedUniformResults', 'completedHealthResults', 'interviewQuestions', 'canManageTests'
        ));
    }

    public function manageCbt()
    {
        $cbtQuestions = PertanyaanCbt::latest()->paginate(Pagination::perPage())->withQueryString();

        return view('admin.tes.cbt-content', compact('cbtQuestions'));
    }

    public function manageInterviewQuestions()
    {
        $interviewQuestions = PertanyaanWawancara::orderBy('sort_order')->orderBy('id')->paginate(Pagination::perPage())->withQueryString();

        return view('admin.tes.interview-questions', compact('interviewQuestions'));
    }

    public function storeInterviewQuestion(Request $request)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'status' => ['nullable', 'boolean'],
        ]);

        PertanyaanWawancara::create([
            'question' => $validated['question'],
            'sort_order' => ((int) PertanyaanWawancara::max('sort_order')) + 1,
            'status' => $request->boolean('status'),
        ]);

        return redirect()->route('admin.tes.interview')->with('success', 'Pertanyaan wawancara ditambahkan.');
    }

    public function updateInterviewQuestion(Request $request, PertanyaanWawancara $question)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'status' => ['nullable', 'boolean'],
        ]);

        $question->update([
            'question' => $validated['question'],
            'status' => $request->boolean('status'),
        ]);

        return redirect()->route('admin.tes.interview')->with('success', 'Pertanyaan wawancara diperbarui.');
    }

    public function destroyInterviewQuestion(PertanyaanWawancara $question)
    {
        $question->delete();

        return redirect()->route('admin.tes.interview')->with('success', 'Pertanyaan wawancara dihapus.');
    }

    public function storeResult(Request $request, Pendaftar $pendaftar)
    {
        $validated = $request->validate([
            'test_name' => ['required', Rule::in(['Baca Tulis Quran', 'Tes CBT', 'Wawancara Orang Tua'])],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $test = TesMasuk::firstOrCreate(
            ['test_name' => $validated['test_name']],
            [
                'test_date' => now()->toDateString(),
                'location' => 'Kampus E SMK Muhammadiyah 4 Cileungsi',
                'description' => $validated['test_name'],
            ]
        );

        PesertaTes::updateOrCreate(
            [
                'test_id' => $test->id,
                'applicant_id' => $pendaftar->id,
            ],
            [
                'attendance' => true,
                'score' => $validated['score'],
                'notes' => $validated['notes'],
            ]
        );

        return redirect()->route($this->routeForTest($validated['test_name']))
            ->with('success', 'Hasil ' . $validated['test_name'] . ' berhasil disimpan.');
    }

    public function storeUniform(Request $request, Pendaftar $pendaftar)
    {
        $validated = $request->validate([
            'uniform_size_id' => ['required', Rule::exists('ukuran_seragam', 'id')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'uniform_size_id.required' => 'Pilih ukuran seragam hasil pengukuran.',
        ]);

        HasilUkurSeragamPendaftar::updateOrCreate(
            ['applicant_id' => $pendaftar->id],
            $validated
        );

        $this->markSimpleTest($pendaftar, 'Tes Ukuran Seragam', 'Ukuran seragam selesai diinput.');

        return redirect()->route($this->routeName('tes.uniform'))
            ->with('success', 'Hasil ukuran seragam berhasil disimpan.');
    }

    public function storeHealth(Request $request, Pendaftar $pendaftar)
    {
        $validated = $request->validate([
            'health' => ['required', 'array'],
            'health.*.result_value' => ['nullable', 'string', 'max:100'],
            'health.*.result_status' => ['nullable', 'string', 'max:50'],
            'health.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated['health'] as $itemId => $result) {
            HasilPemeriksaanKesehatanPendaftar::updateOrCreate(
                [
                    'applicant_id' => $pendaftar->id,
                    'health_check_item_id' => $itemId,
                ],
                [
                    'result_value' => $result['result_value'] ?? null,
                    'result_status' => $result['result_status'] ?? null,
                    'notes' => $result['notes'] ?? null,
                ]
            );
        }

        $this->markSimpleTest($pendaftar, 'Tes Kesehatan', 'Pemeriksaan kesehatan selesai diinput.');

        return redirect()->route($this->routeName('tes.health'))
            ->with('success', 'Hasil tes kesehatan berhasil disimpan.');
    }

    public function destroyResult(Pendaftar $pendaftar, string $testKey)
    {
        $testName = match ($testKey) {
            'btq' => 'Baca Tulis Quran',
            'uniform' => 'Tes Ukuran Seragam',
            'health' => 'Tes Kesehatan',
            'interview' => 'Wawancara Orang Tua',
            default => abort(404),
        };

        $testId = TesMasuk::where('test_name', $testName)->value('id');
        if ($testId) {
            PesertaTes::where('test_id', $testId)->where('applicant_id', $pendaftar->id)->delete();
        }

        if ($testKey === 'uniform') {
            HasilUkurSeragamPendaftar::where('applicant_id', $pendaftar->id)->delete();
        } elseif ($testKey === 'health') {
            HasilPemeriksaanKesehatanPendaftar::where('applicant_id', $pendaftar->id)->delete();
        }

        return redirect()->route($this->routeForTest($testName))
            ->with('success', 'Data hasil tes berhasil dihapus. Peserta kembali ke daftar belum tes.');
    }

    public function storeCbtQuestion(Request $request)
    {
        $validated = $request->validate([
            'question' => ['required', 'string'],
            'option_a' => ['required', 'string', 'max:255'],
            'option_b' => ['required', 'string', 'max:255'],
            'option_c' => ['required', 'string', 'max:255'],
            'option_d' => ['required', 'string', 'max:255'],
            'correct_answer' => ['required', 'in:A,B,C,D'],
            'status' => ['nullable', 'boolean'],
        ]);

        $validated['status'] = $request->boolean('status');

        PertanyaanCbt::create($validated);

        return redirect()->route($this->routeName('tes.cbt'))
            ->with('success', 'Pertanyaan CBT berhasil ditambahkan.');
    }

    public function importCbtQuestions(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:4096'],
        ], [
            'file.required' => 'Pilih file Excel atau CSV soal CBT terlebih dahulu.',
            'file.mimes' => 'File import harus berformat Excel (.xlsx) atau CSV.',
        ]);

        $isXlsx = strtolower($request->file('file')->getClientOriginalExtension()) === 'xlsx';
        $rows = $isXlsx ? $this->readXlsx($request->file('file')->getRealPath()) : null;
        $handle = $isXlsx ? null : fopen($request->file('file')->getRealPath(), 'r');
        $header = $isXlsx ? array_shift($rows) : fgetcsv($handle);
        if (!$header) {
            if ($handle) fclose($handle);
            return back()->with('warning', 'File CSV kosong atau tidak memiliki header.');
        }
        $header = array_map(function ($column) {
            $column = preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $column));
            return strtolower(str_replace([' ', '-'], '_', $column));
        }, $header);
        $created = 0;

        $dataRows = $isXlsx ? $rows : null;
        while (($row = $isXlsx ? array_shift($dataRows) : fgetcsv($handle)) !== false && $row !== null) {
            $data = array_combine($header, array_pad($row, count($header), null));

            $question = $data['pertanyaan'] ?? $data['question'] ?? null;
            if (blank($question)) {
                continue;
            }

            PertanyaanCbt::create([
                'question' => $question,
                'option_a' => $data['pilihan_a'] ?? $data['option_a'] ?? null,
                'option_b' => $data['pilihan_b'] ?? $data['option_b'] ?? null,
                'option_c' => $data['pilihan_c'] ?? $data['option_c'] ?? null,
                'option_d' => $data['pilihan_d'] ?? $data['option_d'] ?? null,
                'correct_answer' => strtoupper(trim($data['jawaban_benar'] ?? $data['correct_answer'] ?? '')) ?: null,
                'status' => filter_var($data['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]);
            $created++;
        }

        if ($handle) fclose($handle);

        return redirect()->route($this->routeName('tes.cbt'))
            ->with('success', "{$created} soal CBT berhasil diimport.");
    }

    public function downloadCbtTemplate()
    {
        return $this->downloadXlsx([
            ['pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'jawaban_benar', 'status'],
            ['Hasil dari 15 + 27 adalah?', '40', '41', '42', '43', 'C', '1'],
            ['Hasil dari 144 dibagi 12 adalah?', '10', '12', '14', '16', 'B', '1'],
            ['Angka berikutnya dari pola 2, 4, 8, 16 adalah?', '18', '20', '24', '32', 'D', '1'],
            ['Sinonim kata "cermat" adalah?', 'Teliti', 'Lambat', 'Ceroboh', 'Ragu-ragu', 'A', '1'],
            ['Antonim kata "optimis" adalah?', 'Semangat', 'Yakin', 'Pesimis', 'Gembira', 'C', '1'],
            ['Bunyi sila ketiga Pancasila adalah?', 'Ketuhanan Yang Maha Esa', 'Persatuan Indonesia', 'Kemanusiaan yang Adil dan Beradab', 'Keadilan Sosial bagi Seluruh Rakyat Indonesia', 'B', '1'],
            ['1 jam 45 menit sama dengan berapa menit?', '90', '100', '105', '115', 'C', '1'],
            ['Luas persegi panjang dengan panjang 8 cm dan lebar 5 cm adalah?', '35 cm2', '40 cm2', '45 cm2', '50 cm2', 'B', '1'],
            ['Tiga perempat dari 20 adalah?', '5', '10', '12', '15', 'D', '1'],
            ['Manakah bilangan yang bukan bilangan prima?', '2', '3', '7', '9', 'D', '1'],
        ], 'template_import_soal_cbt.xlsx');
    }

    public function exportCbtQuestions()
    {
        $rows = [['pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'jawaban_benar', 'status']];
        foreach (PertanyaanCbt::oldest()->get() as $question) {
            $rows[] = [
                $question->question, $question->option_a, $question->option_b,
                $question->option_c, $question->option_d, $question->correct_answer,
                $question->status ? 1 : 0,
            ];
        }
        return $this->downloadXlsx($rows, 'bank_soal_cbt_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function openCbtAccessBulk(Request $request)
    {
        $validated = $request->validate([
            'applicant_ids' => ['required', 'array', 'min:1'],
            'applicant_ids.*' => ['integer', Rule::exists('pendaftar', 'id')],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:240'],
        ], [
            'applicant_ids.required' => 'Pilih minimal satu siswa peserta CBT.',
            'applicant_ids.min' => 'Pilih minimal satu siswa peserta CBT.',
        ]);

        $applicantIds = collect($validated['applicant_ids'])->map(fn ($id) => (int) $id)->unique();
        $resumedCount = 0;

        foreach ($applicantIds as $applicantId) {
            $lockedSession = CbtAccessSession::where('applicant_id', $applicantId)
                ->where('status', 'locked')
                ->where('expires_at', '>', now())
                ->latest()
                ->first();

            if ($lockedSession) {
                $lockedSession->update([
                    'opened_by' => Auth::id(),
                    'status' => 'open',
                    'closed_at' => null,
                    'location_note' => 'Akses CBT dibuka ulang oleh panitia dengan sisa waktu sesi sebelumnya.',
                ]);
                $resumedCount++;
                continue;
            }

            CbtAccessSession::where('applicant_id', $applicantId)->where('status', 'open')->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            CbtAccessSession::create([
                'applicant_id' => $applicantId,
                'opened_by' => Auth::id(),
                'opened_at' => now(),
                'expires_at' => now()->addMinutes((int) $validated['duration_minutes']),
                'location_note' => 'Dibuka panitia untuk peserta yang hadir di lokasi CBT.',
                'status' => 'open',
            ]);
        }

        $newCount = $applicantIds->count() - $resumedCount;
        $message = $resumedCount
            ? "{$resumedCount} siswa melanjutkan CBT dengan sisa waktu sebelumnya."
            : '';
        if ($newCount) {
            $message .= ($message ? ' ' : '') . "{$newCount} siswa mendapat akses CBT baru.";
        }

        return redirect()->route($this->routeName('tes.cbt'))->with('success', $message);
    }

    public function openCbtAccess(Request $request, Pendaftar $pendaftar)
    {
        $validated = $request->validate([
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:240'],
            'location_note' => ['nullable', 'string', 'max:255'],
        ]);

        $lockedSession = CbtAccessSession::where('applicant_id', $pendaftar->id)
            ->where('status', 'locked')
            ->latest()
            ->first();

        if ($lockedSession) {
            if ($lockedSession->expires_at?->isPast()) {
                return redirect()->route($this->routeName('tes.cbt'), ['pendaftar' => $pendaftar->id])
                    ->with('warning', 'Sisa waktu CBT sudah habis. Sesi tidak dapat dilanjutkan.');
            }

            $lockedSession->update([
                'opened_by' => Auth::id(),
                'location_note' => $validated['location_note'] ?? 'Akses CBT dibuka ulang oleh panitia.',
                'status' => 'open',
                'closed_at' => null,
            ]);

            return redirect()->route($this->routeName('tes.cbt'), ['pendaftar' => $pendaftar->id])
                ->with('success', 'Akses CBT dibuka ulang dengan sisa waktu yang sama. Jawaban dan urutan soal sebelumnya tetap tersimpan.');
        }

        CbtAccessSession::where('applicant_id', $pendaftar->id)
            ->where('status', 'open')
            ->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

        CbtAccessSession::create([
            'applicant_id' => $pendaftar->id,
            'opened_by' => Auth::id(),
            'opened_at' => now(),
            'expires_at' => now()->addMinutes((int) $validated['duration_minutes']),
            'location_note' => $validated['location_note'] ?? 'Dibuka saat peserta hadir di lokasi tes.',
            'status' => 'open',
        ]);

        return redirect()->route($this->routeName('tes.cbt'), ['pendaftar' => $pendaftar->id])
            ->with('success', 'Akses CBT siswa berhasil dibuka. Siswa bisa mengerjakan lewat akun masing-masing selama sesi aktif.');
    }

    public function closeCbtAccess(Pendaftar $pendaftar)
    {
        CbtAccessSession::where('applicant_id', $pendaftar->id)
            ->where('status', 'open')
            ->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

        return redirect()->route($this->routeName('tes.cbt'), ['pendaftar' => $pendaftar->id])
            ->with('success', 'Akses CBT siswa ditutup.');
    }

    public function destroyCbtQuestion(PertanyaanCbt $question)
    {
        $question->delete();

        return redirect()->route($this->routeName('tes.cbt'))
            ->with('success', 'Pertanyaan CBT berhasil dihapus.');
    }

    private function markSimpleTest(Pendaftar $pendaftar, string $testName, string $notes): void
    {
        $test = TesMasuk::firstOrCreate(
            ['test_name' => $testName],
            [
                'test_date' => now()->toDateString(),
                'location' => 'Kampus E SMK Muhammadiyah 4 Cileungsi',
                'description' => $testName,
            ]
        );

        PesertaTes::updateOrCreate(
            [
                'test_id' => $test->id,
                'applicant_id' => $pendaftar->id,
            ],
            [
                'attendance' => true,
                'score' => null,
                'notes' => $notes,
            ]
        );
    }

    private function showTest(Request $request, string $testName)
    {
        $request->merge(['jenis_tes' => $testName]);
        return $this->index($request);
    }

    private function routeForTest(string $testName): string
    {
        return $this->routeName(match ($testName) {
            'Baca Tulis Quran' => 'tes.btq',
            'Tes Ukuran Seragam' => 'tes.uniform',
            'Tes Kesehatan' => 'tes.health',
            'Tes CBT' => 'tes.cbt',
            'Wawancara Orang Tua' => 'tes.interview',
        });
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . $name;
    }

    private function downloadXlsx(array $rows, string $filename)
    {
        $path = tempnam(sys_get_temp_dir(), 'cbt_xlsx_');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Soal CBT" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $sheetRows = '';
        foreach ($rows as $rowIndex => $row) {
            $cells = '';
            foreach (array_values($row) as $columnIndex => $value) {
                $reference = $this->columnLetter($columnIndex + 1) . ($rowIndex + 1);
                $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $cells .= '<c r="' . $reference . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
            }
            $sheetRows .= '<row r="' . ($rowIndex + 1) . '">' . $cells . '</row>';
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="1" width="42" customWidth="1"/><col min="2" max="5" width="22" customWidth="1"/><col min="6" max="7" width="16" customWidth="1"/></cols><sheetData>' . $sheetRows . '</sheetData></worksheet>');
        $zip->close();

        return response()->download($path, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    private function readXlsx(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return [];
        $shared = [];
        if (($sharedXml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $xml = simplexml_load_string($sharedXml);
            foreach ($xml->si ?? [] as $item) $shared[] = (string) ($item->t ?? $item->r->t ?? '');
        }
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheetXml === false) return [];
        $xml = simplexml_load_string($sheetXml);
        $rows = [];
        foreach ($xml->sheetData->row ?? [] as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                preg_match('/^[A-Z]+/', $reference, $match);
                $index = $this->columnIndex($match[0] ?? 'A') - 1;
                $type = (string) $cell['t'];
                $value = $type === 'inlineStr' ? (string) $cell->is->t : (string) $cell->v;
                if ($type === 's') $value = $shared[(int) $value] ?? '';
                $values[$index] = $value;
            }
            if ($values) {
                ksort($values);
                $rows[] = array_replace(array_fill(0, max(array_keys($values)) + 1, ''), $values);
            }
        }
        return $rows;
    }

    private function columnLetter(int $number): string
    {
        $letter = '';
        while ($number > 0) { $number--; $letter = chr(65 + ($number % 26)) . $letter; $number = intdiv($number, 26); }
        return $letter;
    }

    private function columnIndex(string $letters): int
    {
        $number = 0;
        foreach (str_split($letters) as $letter) $number = $number * 26 + ord($letter) - 64;
        return $number;
    }

    private function testNames(): array
    {
        return [
            'Baca Tulis Quran',
            'Tes Ukuran Seragam',
            'Tes Kesehatan',
            'Tes CBT',
            'Wawancara Orang Tua',
        ];
    }
}
