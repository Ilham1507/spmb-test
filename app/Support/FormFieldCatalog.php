<?php

namespace App\Support;

use App\Models\SystemSetting;
use App\Models\Pendaftar;

class FormFieldCatalog
{
    public static function groups(): array
    {
        $groups = [
            'Sekolah Asal' => [
                'asal_sekolah' => 'Asal Sekolah',
                'npsn' => 'NPSN Sekolah',
                'alamat_sekolah' => 'Alamat Sekolah',
                'tahun_lulus' => 'Tahun Lulus',
            ],
            'Pilihan Jurusan' => [
                'jurusan' => 'Jurusan',
            ],
            'Biodata' => [
                'nama_peserta' => 'Nama Peserta',
                'nisn' => 'NISN',
                'jenis_kelamin' => 'Jenis Kelamin',
                'tempat_lahir' => 'Tempat Lahir',
                'tanggal_lahir' => 'Tanggal Lahir',
                'agama' => 'Agama',
                'nik' => 'NIK',
                'no_kartu_keluarga' => 'No Kartu Keluarga',
            ],
            'Alamat' => [
                'alamat' => 'Alamat',
                'rt' => 'RT',
                'rw' => 'RW',
                'kelurahan' => 'Kelurahan',
                'kecamatan' => 'Kecamatan',
                'kabupaten_kota' => 'Kabupaten/Kota',
                'provinsi' => 'Provinsi',
                'kode_pos' => 'Kode Pos',
                'jarak_ke_sekolah' => 'Jarak ke Sekolah',
            ],
            'Ayah' => self::parentFields('Ayah'),
            'Ibu' => self::parentFields('Ibu'),
            'Wali' => [
                ...self::parentFields('Wali'),
            ],
            'Kontak' => [
                'no_handphone' => 'No Handphone',
                'email' => 'Email',
            ],
            'Dokumen Pendukung' => [
                'foto_3x4' => 'Foto berwarna 3x4', 'foto_seluruh_badan' => 'Foto Seluruh Badan',
                'skl_skhu_ijazah' => 'SKL/SKHU/Ijazah', 'nilai_rapor_dokumen' => 'Nilai Rapor Semester 1-5',
                'akta_kelahiran' => 'Akta Kelahiran', 'kartu_keluarga' => 'Kartu Keluarga',
                'ktp_orangtua' => 'KTP Orangtua', 'sptjm_orangtua' => 'SPTJM Orangtua',
                'surat_penugasan_instansi' => 'Surat Penugasan dari Instansi',
                'surat_domisili' => 'Surat Keterangan Domisili', 'surat_tidak_mampu' => 'Surat Keterangan Tidak Mampu',
                'kartu_pkh_kps_kip' => 'Kartu PKH/KPS/KIP', 'prestasi' => 'Prestasi Akademik/Non Akademik',
                'berkas_lainnya' => 'Berkas lainnya',
            ],
        ];

        foreach (self::custom() as $field) {
            $groups[$field['group']][$field['key']] = $field['label'];
        }

        $settings = SystemSetting::values();
        $overrides = json_decode($settings['form_field_overrides'] ?? '', true);
        if (is_array($overrides)) {
            foreach ($overrides as $key => $label) {
                foreach ($groups as &$fields) {
                    if (array_key_exists($key, $fields)) $fields[$key] = $label;
                }
                unset($fields);
            }
        }
        $deleted = json_decode($settings['form_deleted_fields'] ?? '', true);
        if (is_array($deleted)) {
            foreach ($groups as &$fields) $fields = array_diff_key($fields, array_flip($deleted));
            unset($fields);
        }

        return $groups;
    }

    public static function custom(): array
    {
        $saved = json_decode(SystemSetting::values()['custom_form_fields'] ?? '', true);
        return is_array($saved) ? array_values(array_filter($saved, fn ($field) => is_array($field) && isset($field['key'], $field['label'], $field['group']))) : [];
    }

    private static function parentFields(string $label): array
    {
        $key = strtolower($label);
        return [
            "nama_{$key}" => "Nama {$label}", "nik_{$key}" => "NIK {$label}",
            "pendidikan_{$key}" => "Pendidikan {$label}", "pekerjaan_{$key}" => "Pekerjaan {$label}",
            "penghasilan_{$key}" => "Penghasilan bulanan {$label}",
            "no_hp_{$key}" => "No Handphone {$label}",
        ];
    }

    public static function keys(): array
    {
        return collect(self::groups())->flatMap(fn (array $fields) => array_keys($fields))->values()->all();
    }

    public static function enabled(): array
    {
        $saved = json_decode(SystemSetting::values()['form_fields'] ?? '', true);
        return is_array($saved)
            ? array_values(array_unique([...array_intersect($saved, self::keys()), 'email']))
            : self::keys();
    }

    public static function isEnabled(string $key): bool
    {
        return in_array($key, self::enabled(), true);
    }

    public static function defaultRequired(): array
    {
        return [
            'asal_sekolah', 'jurusan', 'nama_peserta', 'nisn', 'jenis_kelamin',
            'tempat_lahir', 'tanggal_lahir', 'agama', 'nik', 'no_kartu_keluarga',
            'alamat', 'jarak_ke_sekolah', 'nama_ayah', 'nama_ibu', 'no_handphone',
        ];
    }

    public static function required(): array
    {
        $saved = json_decode(SystemSetting::values()['form_required_fields'] ?? '', true);
        return is_array($saved)
            ? array_values(array_unique(array_intersect($saved, self::keys())))
            : array_values(array_intersect(self::defaultRequired(), self::keys()));
    }

    public static function isRequired(string $key): bool
    {
        return in_array($key, self::required(), true);
    }

    /** Values used as placeholders in old records must not count as completed data. */
    public static function isCompleteValue(mixed $value): bool
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return false;
        }

        $normalized = trim((string) $value);
        return $normalized !== '' && !in_array($normalized, ['-', '—', '–'], true);
    }

    /** Return the actual stored value used to decide whether an enabled field is complete. */
    public static function valueFor(Pendaftar $pendaftar, string $key): mixed
    {
        $fieldLabel = collect(self::groups())->flatMap(fn (array $fields) => $fields)->get($key);
        if (is_string($fieldLabel) && strtolower($fieldLabel) === 'email') {
            return $pendaftar->kontak?->email_verified_at ? $pendaftar->kontak?->email : null;
        }

        if ($key === 'jarak_ke_sekolah') {
            return $pendaftar->alamat?->distance_range ?: $pendaftar->alamat?->distance_to_school;
        }

        $map = [
            'asal_sekolah' => [$pendaftar->sekolahAsal, 'school_name'], 'npsn' => [$pendaftar->sekolahAsal, 'npsn'], 'alamat_sekolah' => [$pendaftar->sekolahAsal, 'school_address'], 'tahun_lulus' => [$pendaftar->sekolahAsal, 'graduation_year'],
            'jurusan' => [$pendaftar, 'major_choice_1'],
            'nama_peserta' => [$pendaftar->biodata, 'full_name'], 'nisn' => [$pendaftar->biodata, 'nisn'],
            'jenis_kelamin' => [$pendaftar->biodata, 'gender'], 'tempat_lahir' => [$pendaftar->biodata, 'birth_place'],
            'tanggal_lahir' => [$pendaftar->biodata, 'birth_date'], 'agama' => [$pendaftar->biodata, 'religion'],
            'nik' => [$pendaftar->biodata, 'nik'], 'no_kartu_keluarga' => [$pendaftar->biodata, 'no_kk'],
            'alamat' => [$pendaftar->alamat, 'address'], 'rt' => [$pendaftar->alamat, 'rt'], 'rw' => [$pendaftar->alamat, 'rw'],
            'kelurahan' => [$pendaftar->alamat, 'village'], 'kecamatan' => [$pendaftar->alamat, 'district'],
            'kabupaten_kota' => [$pendaftar->alamat, 'city'], 'provinsi' => [$pendaftar->alamat, 'province'], 'kode_pos' => [$pendaftar->alamat, 'postal_code'],
            'nama_ayah' => [$pendaftar->dataAyah, 'name'], 'nik_ayah' => [$pendaftar->dataAyah, 'nik'], 'pendidikan_ayah' => [$pendaftar->dataAyah, 'education'], 'pekerjaan_ayah' => [$pendaftar->dataAyah, 'occupation'], 'penghasilan_ayah' => [$pendaftar->dataAyah, 'income'], 'no_hp_ayah' => [$pendaftar->dataAyah, 'phone'],
            'nama_ibu' => [$pendaftar->dataIbu, 'name'], 'nik_ibu' => [$pendaftar->dataIbu, 'nik'], 'pendidikan_ibu' => [$pendaftar->dataIbu, 'education'], 'pekerjaan_ibu' => [$pendaftar->dataIbu, 'occupation'], 'penghasilan_ibu' => [$pendaftar->dataIbu, 'income'], 'no_hp_ibu' => [$pendaftar->dataIbu, 'phone'],
            'nama_wali' => [$pendaftar->dataWali, 'name'], 'nik_wali' => [$pendaftar->dataWali, 'nik'], 'pendidikan_wali' => [$pendaftar->dataWali, 'education'], 'pekerjaan_wali' => [$pendaftar->dataWali, 'occupation'], 'penghasilan_wali' => [$pendaftar->dataWali, 'income'], 'no_hp_wali' => [$pendaftar->dataWali, 'phone'],
            'no_handphone' => [$pendaftar->kontak, 'phone'], 'email' => [$pendaftar->kontak, 'email'],
        ];
        [$model, $attribute] = $map[$key] ?? [null, null];
        return $model && $attribute ? $model->{$attribute} : null;
    }

    public static function requiredStatuses(Pendaftar $pendaftar): array
    {
        $labels = collect(self::groups())->flatMap(fn (array $fields) => $fields);
        return collect(self::required())
            ->filter(fn ($key) => self::isEnabled($key))
            ->mapWithKeys(fn ($key) => [$labels[$key] ?? $key => self::isCompleteValue(self::valueFor($pendaftar, $key))])
            ->all();
    }

    public static function activeStatuses(Pendaftar $pendaftar, array $exceptGroups = []): array
    {
        return collect(self::groups())
            ->except($exceptGroups)
            ->flatMap(fn (array $fields) => $fields)
            ->filter(fn ($label, $key) => self::isEnabled($key))
            ->mapWithKeys(fn ($label, $key) => [$label => self::isCompleteValue(self::valueFor($pendaftar, $key))])
            ->all();
    }
}
