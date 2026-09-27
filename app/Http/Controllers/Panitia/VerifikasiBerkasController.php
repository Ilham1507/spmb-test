<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\DokumenPendaftar;
use App\Models\RiwayatVerifikasiBerkas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VerifikasiBerkasController extends Controller
{
    public function index()
    {
        return redirect()->route($this->routeName('pendaftar.index'), ['status' => 'submitted']);
    }

    public function show(Pendaftar $pendaftar)
    {
        return redirect()->route($this->routeName('pendaftar.show'), $pendaftar);
    }

    public function verify(Request $request, DokumenPendaftar $dokumen)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes'  => 'nullable|string|max:500',
        ]);

        $oldStatus = $dokumen->status;

        $dokumen->update([
            'status'      => $request->status,
            'notes'       => $request->notes,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        // Log riwayat verifikasi
        RiwayatVerifikasiBerkas::create([
            'dokumen_id'        => $dokumen->id,
            'status_lama'       => $oldStatus,
            'status_baru'       => $request->status,
            'catatan'           => $request->notes,
            'diverifikasi_oleh' => Auth::id(),
        ]);

        $pendaftar = Pendaftar::find($dokumen->applicant_id);

        return redirect()->route($this->routeName('pendaftar.show'), $pendaftar->id)
                         ->with('success', 'Status dokumen berhasil diperbarui.');
    }

    public function viewDocument(DokumenPendaftar $dokumen)
    {
        abort_if(!$dokumen->file_path || !Storage::disk('public')->exists($dokumen->file_path), 404, 'File dokumen tidak ditemukan.');

        return response()->file(Storage::disk('public')->path($dokumen->file_path));
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . $name;
    }
}
