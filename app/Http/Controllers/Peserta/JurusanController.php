<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\JalurPendaftaran;
use App\Models\Pendaftar;
use App\Support\SpmbConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class JurusanController extends Controller
{
    public function index()
    {
        $pendaftar = $this->getPendaftar();
        if (!$pendaftar) {
            return redirect()->route('peserta.biodata')
                             ->with('warning', 'Silakan lengkapi biodata terlebih dahulu.');
        }

        $jurusans = Jurusan::where('status', 'aktif')->orderBy('id')->get();
        $jalurs = JalurPendaftaran::where('status', 'aktif')->orderBy('id')->get();
        $maximumChoices = SpmbConfiguration::forAcademicYear($pendaftar->academic_year_id)?->maksimal_pilihan_jurusan ?? 2;
        return view('peserta.jurusan.index', compact('pendaftar', 'jurusans', 'jalurs', 'maximumChoices'));
    }

    public function store(Request $request)
    {
        $pendaftar = $this->getPendaftar();
        $maximumChoices = SpmbConfiguration::forAcademicYear($pendaftar?->academic_year_id)?->maksimal_pilihan_jurusan ?? 2;

        $request->validate([
            'jurusan_id_1' => ['required', Rule::exists('jurusan', 'id')->where('status', 'aktif')],
            'jurusan_id_2' => $maximumChoices > 1 ? ['nullable', 'different:jurusan_id_1', Rule::exists('jurusan', 'id')->where('status', 'aktif')] : ['prohibited'],
            'jalur_pendaftaran_id' => ['required', Rule::exists('jalur_pendaftaran', 'id')->where('status', 'aktif')],
        ], [
            'jurusan_id_1.required' => 'Pilih jurusan utama sesuai daftar jurusan aktif.',
            'jurusan_id_1.exists' => 'Jurusan utama tidak tersedia atau sedang nonaktif.',
            'jurusan_id_2.exists' => 'Jurusan cadangan tidak tersedia atau sedang nonaktif.',
            'jurusan_id_2.different' => 'Jurusan cadangan harus berbeda dari jurusan utama.',
        ]);

        $pendaftar->update([
            'major_choice_1' => $request->jurusan_id_1,
            'major_choice_2' => $request->jurusan_id_2,
            'admission_path_id' => $request->jalur_pendaftaran_id,
        ]);

        return $this->redirectAfterParticipantSave($request, 'peserta.dokumen', 'Pilihan jurusan & jalur masuk berhasil disimpan.', 'peserta.dokumen');
    }

    private function getPendaftar(): ?Pendaftar
    {
        return Auth::user()->pendaftar;
    }
}
