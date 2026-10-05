<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\SekolahAsal;
use App\Models\ReferensiSekolah;
use App\Services\OfficialSchoolDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SekolahAsalController extends Controller
{
    public function index()
    {
        $pendaftar = $this->getPendaftar();
        if (!$pendaftar) {
            return redirect()->route('peserta.biodata')
                             ->with('warning', 'Silakan lengkapi biodata terlebih dahulu.');
        }

        $sekolah = $pendaftar->sekolahAsal;
        return view('peserta.sekolah.index', compact('sekolah', 'pendaftar'));
    }

    public function search(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $juniorHighOnly = true;
        $terms = $this->searchTerms($query);

        if (mb_strlen($query) < 1) {
            return response()->json([]);
        }

        $schools = ReferensiSekolah::query()
            ->where(function ($builder) {
                $builder->whereNull('status')->orWhere('status', '!=', 'nonaktif');
            })
            ->where(function ($builder) use ($query, $terms) {
                $builder->where('npsn', 'like', preg_replace('/\s+/', '', $query).'%')
                    ->orWhere(function ($names) use ($terms) {
                        foreach ($terms as $term) $names->where('nama', 'like', "%{$term}%");
                    })
                    ->orWhere(function ($districts) use ($terms) {
                        foreach ($terms as $term) $districts->where('kecamatan', 'like', "%{$term}%");
                    })
                    ->orWhere(function ($cities) use ($terms) {
                        foreach ($terms as $term) $cities->where('kabupaten_kota', 'like', "%{$term}%");
                    });
            })
            ->when($juniorHighOnly, fn ($builder) => $builder->whereRaw("UPPER(TRIM(bentuk_pendidikan)) IN (?, ?)", ['SMP', 'MTS']))
            ->orderByRaw("CASE WHEN npsn = ? THEN 0 WHEN npsn LIKE ? THEN 1 ELSE 2 END", [$query, "{$query}%"])
            ->orderByRaw("CASE WHEN LOWER(COALESCE(kecamatan, '')) LIKE '%cileungsi%' THEN 0 WHEN LOWER(COALESCE(kabupaten_kota, '')) LIKE '%bogor%' THEN 1 WHEN LOWER(COALESCE(kabupaten_kota, '')) LIKE '%bekasi%' OR LOWER(COALESCE(kabupaten_kota, '')) LIKE '%depok%' OR LOWER(COALESCE(kabupaten_kota, '')) LIKE '%jakarta%' OR LOWER(COALESCE(kabupaten_kota, '')) LIKE '%tangerang%' THEN 2 ELSE 3 END")
            ->orderBy('nama')
            ->limit(15)
            ->get();

        // Return local matches immediately; only missing schools need the network.
        $officialSchools = mb_strlen($query) >= 3 && $schools->isEmpty()
            ? app(OfficialSchoolDirectory::class)->search($query)
            : collect();

        $schools = $schools->concat($officialSchools)
            ->when($juniorHighOnly, fn ($collection) => $collection->filter(
                fn (ReferensiSekolah $school) => in_array(strtoupper(trim((string) $school->bentuk_pendidikan)), ['SMP', 'MTS'], true)
            ))
            ->unique('npsn')
            ->sort(fn (ReferensiSekolah $left, ReferensiSekolah $right) => [
                $this->searchRank($left, $query), $this->proximityRank($left), mb_strtolower((string) $left->nama),
            ] <=> [
                $this->searchRank($right, $query), $this->proximityRank($right), mb_strtolower((string) $right->nama),
            ])
            ->take(15)
            ->values();

        return response()->json($schools->map(fn (ReferensiSekolah $school) => $this->schoolPayload($school)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'referensi_sekolah_id' => ['required', Rule::exists('referensi_sekolah', 'id')],
            'tahun_lulus'    => 'required|integer|min:1990|max:'.(date('Y') + 1),
        ], [
            'referensi_sekolah_id.required' => 'Pilih sekolah dari hasil pencarian sebelum melanjutkan.',
        ]);

        $pendaftar = $this->getPendaftar();
        $reference = ReferensiSekolah::findOrFail($validated['referensi_sekolah_id']);

        if (! in_array(strtoupper(trim((string) $reference->bentuk_pendidikan)), ['SMP', 'MTS'], true)) {
            return back()
                ->withInput()
                ->withErrors(['referensi_sekolah_id' => 'Sekolah asal harus berasal dari jenjang SMP atau sederajat.']);
        }

        SekolahAsal::updateOrCreate(
            ['applicant_id' => $pendaftar->id],
            [
                'referensi_sekolah_id' => $reference?->id,
                'school_name'     => $reference->nama,
                'npsn'            => $reference->npsn,
                'school_address'  => $reference->alamat,
                'bentuk_pendidikan' => $reference->bentuk_pendidikan,
                'status_sekolah' => $reference->status,
                'desa_kelurahan' => $reference->desa_kelurahan,
                'kecamatan' => $reference->kecamatan,
                'kabupaten_kota' => $reference->kabupaten_kota,
                'provinsi' => $reference->provinsi,
                'graduation_year' => $request->tahun_lulus,
            ]
        );

        return $this->redirectAfterParticipantSave($request, 'peserta.jurusan', 'Data sekolah asal berhasil disimpan.', 'peserta.jurusan');
    }

    private function getPendaftar(): ?Pendaftar
    {
        return Auth::user()->pendaftar;
    }

    private function schoolPayload(ReferensiSekolah $school): array
    {
        return [
            'id' => $school->id,
            'npsn' => $school->npsn,
            'nama' => $school->nama,
            'bentuk_pendidikan' => $school->bentuk_pendidikan,
            'status' => $school->status,
            'alamat' => $school->alamat,
            'desa_kelurahan' => $school->desa_kelurahan,
            'kecamatan' => $school->kecamatan,
            'kabupaten_kota' => $school->kabupaten_kota,
            'provinsi' => $school->provinsi,
            'alamat_lengkap' => $school->alamat_lengkap,
        ];
    }

    /** Prioritise the school's immediate catchment area when search relevance is the same. */
    private function proximityRank(ReferensiSekolah $school): int
    {
        $district = mb_strtolower((string) $school->kecamatan);
        $city = mb_strtolower((string) $school->kabupaten_kota);

        if (str_contains($district, 'cileungsi')) {
            return 0;
        }
        if (str_contains($city, 'bogor')) {
            return 1;
        }
        if (str_contains($city, 'bekasi') || str_contains($city, 'depok') || str_contains($city, 'jakarta') || str_contains($city, 'tangerang')) {
            return 2;
        }

        return 3;
    }

    private function searchRank(ReferensiSekolah $school, string $query): int
    {
        $query = mb_strtolower($query);
        $npsn = (string) $school->npsn;
        $name = mb_strtolower((string) $school->nama);

        if ($npsn === $query || $name === $query) {
            return 0;
        }

        return str_starts_with($npsn, $query) || str_starts_with($name, $query) ? 1 : 2;
    }

    private function searchTerms(string $query): array
    {
        $terms = collect(preg_split('/[^\pL\pN]+/u', mb_strtolower($query)) ?: [])
            ->filter(fn (string $term) => $term !== '')
            ->unique()
            ->take(5)
            ->values()
            ->all();

        return $terms ?: [mb_strtolower($query)];
    }

}
