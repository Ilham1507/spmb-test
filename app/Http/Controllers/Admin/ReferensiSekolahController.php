<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferensiSekolah;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

        if (mb_strlen($query) < 1) {
            return response()->json([]);
        }

        $schools = ReferensiSekolah::query()
            ->where(function ($builder) {
                $builder->whereNull('status')->orWhere('status', '!=', 'nonaktif');
            })
            ->where(function ($builder) use ($query) {
                $builder->where('nama', 'like', "%{$query}%")
                    ->orWhere('npsn', 'like', "{$query}%")
                    ->orWhere('kecamatan', 'like', "%{$query}%")
                    ->orWhere('kabupaten_kota', 'like', "%{$query}%");
            })
            ->whereRaw("UPPER(TRIM(bentuk_pendidikan)) IN (?, ?)", self::JUNIOR_HIGH_FORMS)
            ->orderByRaw("CASE WHEN npsn = ? THEN 0 WHEN npsn LIKE ? THEN 1 ELSE 2 END", [$query, "{$query}%"])
            ->orderBy('nama')
            ->limit(15)
            ->get();

        $officialSchools = mb_strlen($query) >= 3 && $schools->count() < 5
            ? $this->findAndCacheOfficialSchools($query)
            : collect();

        return response()->json(
            $schools->concat($officialSchools)
                ->filter(fn (ReferensiSekolah $school) => in_array(strtoupper(trim((string) $school->bentuk_pendidikan)), self::JUNIOR_HIGH_FORMS, true))
                ->unique('npsn')
                ->take(15)
                ->values()
                ->map(fn (ReferensiSekolah $school) => $this->schoolPayload($school))
        );
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

    private function findAndCacheOfficialSchools(string $query)
    {
        return Cache::remember('admin-official-school-search:'.sha1(mb_strtolower($query)), now()->addMinutes(15), function () use ($query) {
            try {
                $search = $this->officialHttpClient()
                    ->timeout(8)
                    ->accept('text/html')
                    ->get('https://referensi.data.kemendikdasmen.go.id/pendidikan/cari/'.rawurlencode($query));

                if (! $search->successful()) {
                    return collect();
                }

                preg_match_all('~pendidikan/npsn/(\\d{8})~', $search->body(), $matches);

                return collect($matches[1] ?? [])->unique()->take(10)
                    ->map(fn (string $npsn) => $this->fetchAndCacheOfficialSchool($npsn))
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
            preg_match('~<h4>\\s*(.*?)\\s*</h4>~is', $html, $nameMatch);
            $name = $this->cleanOfficialValue($nameMatch[1] ?? '');

            if ($name === '') {
                return null;
            }

            $school = ReferensiSekolah::updateOrCreate(
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

            return in_array(strtoupper(trim((string) $school->bentuk_pendidikan)), self::JUNIOR_HIGH_FORMS, true)
                ? $school
                : null;
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
        return trim((string) preg_replace('/\\s+/', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
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
