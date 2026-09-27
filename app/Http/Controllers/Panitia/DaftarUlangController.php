<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\DaftarUlang;
use App\Support\ReRegistrationFee;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DaftarUlangController extends Controller
{
    public function index()
    {
        // Only accepted students can re-register
        $pendaftars = Pendaftar::with(['biodata', 'jurusan1'])
            ->where('registration_status', 'accepted')
            ->latest()
            ->paginate(Pagination::perPage())->withQueryString();

        return view('panitia.daftar_ulang.index', compact('pendaftars'));
    }

    public function process(Request $request, Pendaftar $pendaftar)
    {
        $request->validate([
            'status' => 'required|in:completed',
            'notes'  => 'nullable|string|max:500',
        ]);

        $pendaftar->update([
            'registration_status' => 're_registered'
        ]);

        ReRegistrationFee::ensureBill($pendaftar->refresh());

        DaftarUlang::updateOrCreate(
            ['applicant_id' => $pendaftar->id],
            [
                'reregistration_date' => now(),
                'status'              => 'completed',
                'notes'               => $request->notes,
            ]
        );

        return redirect()->route($this->routeName('daftar_ulang.index'))
                         ->with('success', 'Siswa berhasil didaftar ulang.');
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . $name;
    }
}
