<?php

namespace App\Services;

use App\Models\ReferensiSekolah;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OfficialSchoolDirectory
{
    private const BASE_URL = 'https://referensi.data.kemendikdasmen.go.id/pendidikan/';
    private const BUDGET_SECONDS = 3.0;
    private const MAX_DETAILS = 2;

    public function search(string $query): Collection
    {
        $query = trim($query);
        if (mb_strlen($query) < 3) {
            return collect();
        }

        $key = 'official-school-search-v2:'.sha1(mb_strtolower($query));
        if (($ids = Cache::get($key)) !== null) {
            return $this->schools($ids);
        }

        // Never queue behind another slow upstream lookup, even across roles.
        $lock = Cache::lock('official-school-directory', 8);
        if (! $lock->get()) {
            return collect();
        }

        $schools = collect();
        try {
            if (Cache::has('official-school-directory-cooldown')) {
                return $schools;
            }

            $deadline = $this->monotonicTime() + self::BUDGET_SECONDS;
            if (preg_match('/^\d{8}$/', $query)) {
                $npsns = [$query];
            } else {
                $response = $this->client($deadline)->get(self::BASE_URL.'cari/'.rawurlencode($query));
                $response->throw();
                preg_match_all('~pendidikan/npsn/(\d{8})~', $response->body(), $matches);
                $npsns = array_values(array_unique($matches[1] ?? []));
            }

            // One search, at most two detail requests, all sharing one deadline.
            foreach (array_slice($npsns, 0, self::MAX_DETAILS) as $npsn) {
                if ($deadline - $this->monotonicTime() < 0.1) {
                    break;
                }
                $existing = ReferensiSekolah::where('npsn', $npsn)->first();
                if ($existing) {
                    if ($existing->status !== 'nonaktif') {
                        $schools->push($existing);
                    }
                    continue;
                }
                $response = $this->client($deadline)->get(self::BASE_URL.'npsn/'.$npsn);
                $response->throw();
                $html = $response->body();
                preg_match('~<h4>\s*(.*?)\s*</h4>~is', $html, $nameMatch);
                $name = $this->clean($nameMatch[1] ?? '');
                $form = $this->field($html, 'Bentuk Pendidikan');
                if ($name === '' || ! in_array(strtoupper(trim((string) $form)), ['SMP', 'MTS'], true)) {
                    continue;
                }
                $schools->push(ReferensiSekolah::updateOrCreate(['npsn' => $npsn], [
                    'nama' => $name,
                    'alamat' => $this->field($html, 'Alamat'),
                    'desa_kelurahan' => $this->field($html, 'Desa/Kelurahan'),
                    'kecamatan' => $this->field($html, 'Kecamatan/Kota (LN)'),
                    'kabupaten_kota' => $this->field($html, 'Kab.-Kota/Negara (LN)'),
                    'provinsi' => $this->field($html, 'Propinsi/Luar Negeri (LN)'),
                    'status' => $this->field($html, 'Status Sekolah'),
                    'bentuk_pendidikan' => $form,
                ]));
            }
        } catch (\Throwable) {
            // A failing directory must not exhaust every web worker.
            Cache::put('official-school-directory-cooldown', true, 30);
        } finally {
            $lock->release();
        }

        Cache::put($key, $schools->pluck('id')->all(), $schools->isEmpty() ? 60 : 21600);
        return $schools;
    }

    protected function monotonicTime(): float
    {
        return hrtime(true) / 1_000_000_000;
    }

    private function client(float $deadline): PendingRequest
    {
        $remaining = max(0.1, $deadline - $this->monotonicTime());
        $request = Http::connectTimeout(min(0.5, $remaining))
            ->timeout(min(1.25, $remaining))->accept('text/html');
        if ($caBundle = config('payments.midtrans.ca_bundle')) {
            $request = $request->withOptions(['verify' => $caBundle]);
        }
        return $request;
    }

    private function schools(array $ids): Collection
    {
        return ReferensiSekolah::whereIn('id', $ids)
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'nonaktif'))
            ->get();
    }

    private function field(string $html, string $label): ?string
    {
        $label = preg_quote($label, '~');
        return preg_match("~<td[^>]*>\\s*{$label}\\s*</td>\\s*<td[^>]*>.*?</td>\\s*<td[^>]*>(.*?)</td>~is", $html, $match)
            ? ($this->clean($match[1]) ?: null) : null;
    }

    private function clean(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
