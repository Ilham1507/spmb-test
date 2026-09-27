<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\RekeningSekolah;
use Illuminate\Http\Request;
use App\Support\Pagination;

class RekeningController extends Controller
{
    public function index()
    {
        $rekenings = RekeningSekolah::latest('status')
            ->latest('id')
            ->paginate(Pagination::perPage())->withQueryString();

        return view('bendahara.rekening.index', compact('rekenings'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $validated['status'] = $request->boolean('status');

        RekeningSekolah::create($validated);

        return back()->with('success', 'Rekening resmi berhasil ditambahkan.');
    }

    public function update(Request $request, RekeningSekolah $rekening)
    {
        $validated = $this->validatedData($request);
        $validated['status'] = $request->boolean('status');

        $rekening->update($validated);

        return back()->with('success', 'Rekening resmi berhasil diperbarui.');
    }

    public function toggle(RekeningSekolah $rekening)
    {
        $rekening->update([
            'status' => ! (bool) $rekening->status,
        ]);

        return back()->with('success', $rekening->status ? 'Rekening diaktifkan.' : 'Rekening dinonaktifkan.');
    }

    public function destroy(RekeningSekolah $rekening)
    {
        $rekening->delete();

        return back()->with('success', 'Rekening resmi berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'nama_bank' => ['required', 'string', 'max:100'],
            'nomor_rekening' => ['required', 'string', 'max:100'],
            'atas_nama' => ['required', 'string', 'max:150'],
        ], [
            'nama_bank.required' => 'Nama bank wajib diisi.',
            'nomor_rekening.required' => 'Nomor rekening wajib diisi.',
            'atas_nama.required' => 'Nama pemilik rekening wajib diisi.',
        ]);
    }
}
