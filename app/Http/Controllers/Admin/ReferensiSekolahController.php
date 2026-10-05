<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferensiSekolah;
use App\Services\OfficialSchoolDirectory;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReferensiSekolahController extends Controller
{
    private const JUNIOR_HIGH_FORMS = ['SMP', 'MTS'];

    public function index(Request $request)
    {
        $sekolahs = ReferensiSekolah::query()
            ->whereRaw("UPPER(TRIM(bentuk_pendidikan)) IN (?, ?)", self::JUNIOR_HIGH_FORMS)
            ->when($request->filled('search'), function ($queryBuilder) use ($request) {
                $query = trim((string) $request->search);
                $queryBuilder->where(function ($builder) use ($query) {
                    $builder->where('nama', 'like', "%{$query}%")
                        ->orWhere('npsn', 'like', "{$query}%")
                        ->orWhere('kecamatan', 'like', "%{$query}%")
                        ->orWhere('kabupaten_kota', 'like', "%{$query}%");
                });
            })
            ->orderBy('nama')
            ->paginate(Pagination::perPage())
            ->withQueryString();

        return view('admin.master.sekolah', compact('sekolahs'));
    }

    public function search(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
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
            ->whereRaw("UPPER(TRIM(bentuk_pendidikan)) IN (?, ?)", self::JUNIOR_HIGH_FORMS)
            ->orderByRaw("CASE WHEN npsn = ? THEN 0 WHEN npsn LIKE ? THEN 1 ELSE 2 END", [$query, "{$query}%"])
            ->orderByRaw("CASE WHEN LOWER(COALESCE(kecamatan, '')) LIKE '%cileungsi%' THEN 0 WHEN LOWER(COALESCE(kabupaten_kota, '')) LIKE '%bogor%' THEN 1 WHEN LOWER(COALESCE(kabupaten_kota, '')) LIKE '%bekasi%' OR LOWER(COALESCE(kabupaten_kota, '')) LIKE '%depok%' OR LOWER(COALESCE(kabupaten_kota, '')) LIKE '%jakarta%' OR LOWER(COALESCE(kabupaten_kota, '')) LIKE '%tangerang%' THEN 2 ELSE 3 END")
            ->orderBy('nama')
            ->limit(15)
            ->get();

        $officialSchools = mb_strlen($query) >= 3 && $schools->isEmpty()
            ? app(OfficialSchoolDirectory::class)->search($query)
            : collect();

        return response()->json(
            $schools->concat($officialSchools)
                ->filter(fn (ReferensiSekolah $school) => in_array(strtoupper(trim((string) $school->bentuk_pendidikan)), self::JUNIOR_HIGH_FORMS, true))
                ->unique('npsn')
                ->sort(fn (ReferensiSekolah $left, ReferensiSekolah $right) => [
                    $this->searchRank($left, $query), $this->proximityRank($left), mb_strtolower((string) $left->nama),
                ] <=> [
                    $this->searchRank($right, $query), $this->proximityRank($right), mb_strtolower((string) $right->nama),
                ])
                ->take(15)
                ->values()
                ->map(fn (ReferensiSekolah $school) => $this->schoolPayload($school))
        );
    }

    private function proximityRank(ReferensiSekolah $school): int
    {
        $district = mb_strtolower((string) $school->kecamatan);
        $city = mb_strtolower((string) $school->kabupaten_kota);

        if (str_contains($district, 'cileungsi')) return 0;
        if (str_contains($city, 'bogor')) return 1;
        if (str_contains($city, 'bekasi') || str_contains($city, 'depok') || str_contains($city, 'jakarta') || str_contains($city, 'tangerang')) return 2;

        return 3;
    }

    private function searchRank(ReferensiSekolah $school, string $query): int
    {
        $query = mb_strtolower($query);
        $npsn = (string) $school->npsn;
        $name = mb_strtolower((string) $school->nama);

        if ($npsn === $query || $name === $query) return 0;

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

    public function store(Request $request)
    {
        $data = $request->validate([
            'npsn' => ['required', 'digits:8'],
            'nama' => ['required', 'string', 'max:150'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'bentuk_pendidikan' => ['required', Rule::in(self::JUNIOR_HIGH_FORMS)],
            'status' => ['nullable', 'string', 'max:30'],
            'desa_kelurahan' => ['nullable', 'string', 'max:150'],
            'kecamatan' => ['nullable', 'string', 'max:150'],
            'kabupaten_kota' => ['nullable', 'string', 'max:150'],
            'provinsi' => ['nullable', 'string', 'max:150'],
        ], [
            'bentuk_pendidikan.required' => 'Pilih sekolah SMP atau MTs dari hasil pencarian.',
            'bentuk_pendidikan.in' => 'Hanya sekolah jenjang SMP atau MTs yang dapat ditambahkan.',
        ]);

        $data['status'] = $data['status'] ?: 'aktif';
        ReferensiSekolah::updateOrCreate(['npsn' => $data['npsn']], $data);

        return back()->with('success', 'Referensi SMP/MTs berhasil disimpan.');
    }

    public function update(Request $request, ReferensiSekolah $sekolah)
    {
        $data = $request->validate([
            'npsn' => ['required', 'digits:8', Rule::unique('referensi_sekolah', 'npsn')->ignore($sekolah->id)],
            'nama' => 'required|string|max:150',
            'alamat' => 'nullable|string|max:500',
            'status' => 'required|string|max:30',
        ]);
        $sekolah->update($data);

        return back()->with('success', 'Asal sekolah berhasil diperbarui.');
    }

    public function destroy(ReferensiSekolah $sekolah)
    {
        $sekolah->update(['status' => 'nonaktif']);

        return back()->with('success', 'Asal sekolah dinonaktifkan.');
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

}
