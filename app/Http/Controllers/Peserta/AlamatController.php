<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\AlamatPendaftar;
use App\Models\Pendaftar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\FormFieldCatalog;

class AlamatController extends Controller
{
    public function index()
    {
        $pendaftar = $this->getPendaftar();
        if (!$pendaftar) {
            return redirect()->route('peserta.biodata')
                             ->with('warning', 'Silakan lengkapi biodata terlebih dahulu.');
        }

        $alamat = $pendaftar->alamat;
        return view('peserta.alamat.index', compact('alamat', 'pendaftar'));
    }

    public function store(Request $request)
    {
        $required = fn (string $key, string $rules) => FormFieldCatalog::isRequired($key) ? "required|{$rules}" : "nullable|{$rules}";
        $request->validate([
            'alamat'     => $required('alamat', 'string'),
            'rt'         => 'nullable|string|max:5',
            'rw'         => 'nullable|string|max:5',
            'kelurahan'  => 'nullable|string|max:255',
            'kecamatan'  => 'nullable|string|max:255',
            'kota'       => 'nullable|string|max:255',
            'provinsi'   => 'nullable|string|max:255',
            'kode_pos'   => 'nullable|string|max:10',
            'jarak_ke_sekolah' => 'nullable|string|max:50',
        ]);

        $pendaftar = $this->getPendaftar();
        $distancePoint = $this->distancePoint($request->jarak_ke_sekolah);
        $distanceMeter = $this->distanceMeter($request->jarak_ke_sekolah);
        
        AlamatPendaftar::updateOrCreate(
            ['applicant_id' => $pendaftar->id],
            [
                'address'      => $request->alamat,
                'rt'           => $request->rt,
                'rw'           => $request->rw,
                'village'      => $request->kelurahan,
                'district'     => $request->kecamatan,
                'city'         => $request->kota,
                'province'     => $request->provinsi,
                'postal_code'  => $request->kode_pos,
                'distance_to_school' => $distanceMeter,
                'distance_range' => $request->jarak_ke_sekolah,
                'distance_point' => $distancePoint,
            ]
        );

        return $this->redirectAfterParticipantSave($request, 'peserta.ayah', 'Alamat berhasil disimpan.', 'peserta.ayah');
    }

    private function getPendaftar(): ?Pendaftar
    {
        return Auth::user()->pendaftar;
    }

    private function distancePoint(?string $distance): ?int
    {
        return match ($distance) {
            '0 - 1000 meter' => 500,
            '1001 - 3000 meter' => 400,
            '3001 - 5000 meter' => 300,
            '5001 - 10000 meter' => 200,
            'Lebih dari 10000 meter' => 100,
            default => null,
        };
    }

    private function distanceMeter(?string $distance): ?int
    {
        return match ($distance) {
            '0 - 1000 meter' => 1000,
            '1001 - 3000 meter' => 3000,
            '3001 - 5000 meter' => 5000,
            '5001 - 10000 meter' => 10000,
            'Lebih dari 10000 meter' => 10001,
            default => null,
        };
    }
}
