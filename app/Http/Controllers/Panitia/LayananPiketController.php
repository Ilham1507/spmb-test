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

class LayananPiketController extends Controller
{
    public function index(Request $request)
    {
        $visits = KunjunganPendaftar::with(['penerima.role', 'pendaftar'])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('full_name', 'like', '%'.$request->search.'%')
                ->orWhere('visitor_phone', 'like', '%'.$request->search.'%')
                ->orWhere('origin_school', 'like', '%'.$request->search.'%')))
            ->latest('visited_at')->paginate(Pagination::perPage())->withQueryString();
        $jurusans = Jurusan::where('status', 'aktif')->orderBy('name')->get();
        $visitsToday = KunjunganPendaftar::whereDate('visited_at', today())->count();
        $unregisteredCount = KunjunganPendaftar::whereNull('applicant_id')->count();

        return view('panitia.kunjungan.index', compact('visits', 'jurusans', 'visitsToday', 'unregisteredCount'));
    }

    public function searchSchool(Request $request)
    {
        $request->merge(['junior_high_only' => true]);

        return app(SekolahAsalController::class)->search($request);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visit_purpose' => ['required', 'in:information,plan_to_register,direct_registration'],
            'full_name' => ['required', 'string', 'max:150'],
            'visitor_phone' => ['required', 'string', 'regex:/^08[0-9]{8,13}$/'],
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
        $validated['normalized_full_name'] = FullNameNormalizer::normalize($validated['full_name']);
        $validated['origin_school'] = $reference->nama;
        $validated['origin_school_npsn'] = $reference->npsn;
        $major = Jurusan::where('status', 'aktif')->findOrFail($validated['interested_major_id']);
        $validated['major_interest'] = $major->name;
        unset($validated['referensi_sekolah_id']);

        KunjunganPendaftar::create($validated + [
            'applicant_id' => null,
            'visited_at' => now(),
            'received_by' => Auth::id(),
        ]);

        return back()->with('success', 'Data calon siswa berhasil dicatat. Guru penerima: '.Auth::user()->name.'. Siswa dapat melakukan registrasi biasa dari perangkatnya.');
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        return str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
    }
}
