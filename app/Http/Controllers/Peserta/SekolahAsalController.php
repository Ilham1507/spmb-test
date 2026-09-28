<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\SekolahAsal;
use App\Models\ReferensiSekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

        // Lengkapi hasil lokal dengan pencarian referensi resmi agar sekolah
        // baru tetap langsung muncul saat siswa mengetik namanya.
        $officialSchools = mb_strlen($query) >= 3 && $schools->count() < 5
            ? $this->findAndCacheOfficialSchools($query)
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

    private function findAndCacheOfficialSchools(string $query)
    {
        return Cache::remember('official-school-search:'.sha1(mb_strtolower($query)), now()->addMinutes(15), function () use ($query) {
            try {
                // The official directory is less forgiving with multi-word names.
                // Retry each meaningful word, e.g. "al furqon" also searches "furqon".
                $lookups = collect([$query])
                    ->concat(collect($this->searchTerms($query))->filter(fn (string $term) => mb_strlen($term) >= 3))
                    ->unique()
                    ->values();
                $npsns = $lookups->flatMap(function (string $lookup) {
                    $search = $this->officialHttpClient()
                        ->timeout(8)
                        ->accept('text/html')
                        ->get('https://referensi.data.kemendikdasmen.go.id/pendidikan/cari/'.rawurlencode($lookup));

                    if (! $search->successful()) return [];

                    preg_match_all('~pendidikan/npsn/(\d{8})~', $search->body(), $matches);
                    return $matches[1] ?? [];
                })->unique()->take(10);

                return $npsns->map(fn (string $npsn) => $this->fetchAndCacheOfficialSchool($npsn))
                ->filter()
                ->values();
            } catch (\Throwable) {
                return collect();
            }
        });
    }

    private function fetchAndCacheOfficialSchool(string $npsn): ?ReferensiSekolah
    {
        try {
            $response = $this->officialHttpClient()
                ->timeout(8)
                ->accept('text/html')
                ->get("https://referensi.data.kemendikdasmen.go.id/pendidikan/npsn/{$npsn}");

            if (! $response->successful()) {
                return null;
            }

            $html = $response->body();
            preg_match('~<h4>\s*(.*?)\s*</h4>~is', $html, $nameMatch);
            $name = $this->cleanOfficialValue($nameMatch[1] ?? '');

            if ($name === '') {
                return null;
            }

            return ReferensiSekolah::updateOrCreate(
                ['npsn' => $npsn],
                [
                    'nama' => $name,
                    'alamat' => $this->officialField($html, 'Alamat'),
                    'desa_kelurahan' => $this->officialField($html, 'Desa/Kelurahan'),
                    'kecamatan' => $this->officialField($html, 'Kecamatan/Kota (LN)'),
                    'kabupaten_kota' => $this->officialField($html, 'Kab.-Kota/Negara (LN)'),
                    'provinsi' => $this->officialField($html, 'Propinsi/Luar Negeri (LN)'),
                    'status' => $this->officialField($html, 'Status Sekolah'),
                    'bentuk_pendidikan' => $this->officialField($html, 'Bentuk Pendidikan'),
                ]
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function officialField(string $html, string $label): ?string
    {
        $quotedLabel = preg_quote($label, '~');
        $pattern = "~<td[^>]*>\\s*{$quotedLabel}\\s*</td>\\s*<td[^>]*>.*?</td>\\s*<td[^>]*>(.*?)</td>~is";

        return preg_match($pattern, $html, $match)
            ? ($this->cleanOfficialValue($match[1]) ?: null)
            : null;
    }

    private function cleanOfficialValue(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function officialHttpClient()
    {
        $request = Http::retry(1, 200);

        if ($caBundle = config('payments.midtrans.ca_bundle')) {
            $request = $request->withOptions(['verify' => $caBundle]);
        }

        return $request;
    }
}
