<?php
namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Pendaftar;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AuditTrailObserver
{
    public function saving(Model $model): void
    {
        if (! Schema::hasTable('tahun_ajaran') || ! Schema::hasColumn('tahun_ajaran', 'is_archived')) return;
        $yearId = $model instanceof Pendaftar ? $model->academic_year_id : ($model->applicant_id ? Pendaftar::whereKey($model->applicant_id)->value('academic_year_id') : null);
        if ($yearId && TahunAjaran::whereKey($yearId)->where('is_archived', true)->exists()) {
            throw ValidationException::withMessages(['periode' => 'Data periode yang sudah diarsipkan tidak dapat diubah. Buka arsip periode terlebih dahulu bila perlu.']);
        }
    }
    public function deleting(Model $model): void { $this->saving($model); }

    public function created(Model $model): void { $this->write('dibuat', $model, [], $this->clean($model->getAttributes())); }
    public function updated(Model $model): void
    {
        $changed = collect($model->getChanges())->except(['updated_at'])->all();
        if ($changed) $this->write('diubah', $model, collect($changed)->mapWithKeys(fn ($value, $key) => [$key => ['sebelum' => $this->mask($key, $model->getOriginal($key)), 'sesudah' => $this->mask($key, $value)]])->all());
    }
    public function deleted(Model $model): void { $this->write('dihapus', $model, [], $this->clean($model->getAttributes())); }

    private function write(string $action, Model $model, array $changes = [], array $created = []): void
    {
        if (! Schema::hasTable('audit_logs')) return;
        [$category, $label] = $this->subject($model);
        AuditLog::create([
            'actor_id' => Auth::id(), 'action' => $action, 'subject_type' => $model::class, 'subject_id' => $model->getKey(),
            'category' => $category, 'subject_label' => $label,
            'changes' => $changes ?: ($created ? ['data' => $created] : null),
            'route_name' => request()?->route()?->getName(),
        ]);
    }

    private function subject(Model $model): array
    {
        $class = class_basename($model);
        $map = [
            'Pendaftar' => 'Pendaftaran', 'BiodataPendaftar' => 'Data siswa', 'AlamatPendaftar' => 'Data siswa',
            'DataAyah' => 'Data siswa', 'DataIbu' => 'Data siswa', 'DataWali' => 'Data siswa', 'SekolahAsal' => 'Data siswa', 'KontakPendaftar' => 'Data siswa',
            'GelombangJurusan' => 'Biaya jurusan', 'PesertaTes' => 'Hasil tes', 'HasilPemeriksaanKesehatanPendaftar' => 'Hasil tes', 'HasilUkurSeragamPendaftar' => 'Hasil tes',
        ];
        $category = $map[$class] ?? $class;
        $applicantId = $model instanceof Pendaftar ? $model->id : ($model->applicant_id ?? null);
        $applicant = $applicantId ? Pendaftar::with('biodata')->find($applicantId) : null;
        $applicantLabel = $applicant ? (($applicant->registration_number ?: 'Pendaftar #'.$applicant->id) . ' — ' . ($applicant->biodata?->full_name ?? 'Tanpa nama')) : null;
        return [$category, $applicantLabel ?? match ($class) {
            'GelombangJurusan' => 'Rincian biaya #'.$model->id,
            default => $class.' #'.$model->id,
        }];
    }

    private function clean(array $attributes): array
    {
        return collect($attributes)->except(['id', 'created_at', 'updated_at'])->mapWithKeys(fn ($value, $key) => [$key => $this->mask($key, $value)])->all();
    }
    private function mask(string $key, mixed $value): mixed
    {
        if (in_array($key, ['nik', 'no_kk', 'phone', 'password'], true) && filled($value)) return '••••••' . substr((string) $value, -4);
        return is_scalar($value) || $value === null ? $value : json_encode($value);
    }
}
