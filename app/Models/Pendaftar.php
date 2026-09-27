<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pendaftar extends Model
{
    protected $table = 'pendaftar';
    protected $guarded = ['id'];

    protected $casts = [
        'correction_submitted_at' => 'datetime',
        'preferred_test_selected_at' => 'datetime',
        'selection_reminder_sent_on' => 'date',
    ];

    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function tahunAjaran() { return $this->belongsTo(TahunAjaran::class, 'academic_year_id'); }
    public function biodata() { return $this->hasOne(BiodataPendaftar::class, 'applicant_id'); }
    public function alamat() { return $this->hasOne(AlamatPendaftar::class, 'applicant_id'); }
    public function dataAyah() { return $this->hasOne(DataAyah::class, 'applicant_id'); }
    public function dataIbu() { return $this->hasOne(DataIbu::class, 'applicant_id'); }
    public function dataWali() { return $this->hasOne(DataWali::class, 'applicant_id'); }
    public function sekolahAsal() { return $this->hasOne(SekolahAsal::class, 'applicant_id'); }
    public function gelombangPendaftaran() { return $this->belongsTo(GelombangPendaftaran::class, 'wave_id'); }
    public function jalurPendaftaran() { return $this->belongsTo(JalurPendaftaran::class, 'admission_path_id'); }
    public function preferredTestSchedule() { return $this->belongsTo(JadwalSpmb::class, 'preferred_test_schedule_id'); }
    public function jurusan1() { return $this->belongsTo(Jurusan::class, 'major_choice_1'); }
    public function jurusan2() { return $this->belongsTo(Jurusan::class, 'major_choice_2'); }
    public function dokumenPendaftars() { return $this->hasMany(DokumenPendaftar::class, 'applicant_id'); }
    public function kontak() { return $this->hasOne(KontakPendaftar::class, 'applicant_id'); }
    public function kunjungan() { return $this->hasMany(KunjunganPendaftar::class, 'applicant_id'); }
    public function kunjunganPenerimaanUtama(): ?KunjunganPendaftar
    {
        $visits = $this->relationLoaded('kunjungan')
            ? $this->kunjungan
            : $this->kunjungan()->with('penerima')->get();

        return $visits->sortByDesc(fn (KunjunganPendaftar $visit) => sprintf(
            '%d-%s',
            $visit->visit_purpose === 'direct_registration' ? 1 : 0,
            $visit->visited_at?->format('YmdHis') ?? '00000000000000'
        ))->first();
    }
    public function hasilSeleksi() { return $this->hasOne(HasilSeleksi::class, 'applicant_id'); }
    public function statusHistory() { return $this->hasMany(RiwayatStatusPendaftar::class, 'pendaftar_id'); }
    public function pesertaTes() { return $this->hasMany(PesertaTes::class, 'applicant_id'); }
    public function healthChecks() { return $this->hasMany(HasilPemeriksaanKesehatanPendaftar::class, 'applicant_id'); }
    public function uniformMeasurement() { return $this->hasOne(HasilUkurSeragamPendaftar::class, 'applicant_id'); }
    public function tagihan() { return $this->hasMany(TagihanPendaftar::class, 'applicant_id'); }

}
