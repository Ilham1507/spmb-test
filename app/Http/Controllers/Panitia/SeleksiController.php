<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\HasilSeleksi;
use App\Models\RiwayatStatusPendaftar;
use App\Support\ReRegistrationFee;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SeleksiController extends Controller
{
    public function index(Request $request)
    {
        // Pendaftar yang sudah verified dan siap diseleksi
        $pendaftars = Pendaftar::with(['biodata', 'jurusan1', 'jurusan2', 'hasilSeleksi.major', 'hasilSeleksi.decisionMaker'])
            ->whereIn('registration_status', ['verified', 'accepted', 'rejected'])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('registration_number', 'like', '%'.$request->search.'%')
                ->orWhereHas('biodata', fn ($biodata) => $biodata->where('full_name', 'like', '%'.$request->search.'%'))))
            ->latest()
            ->paginate(Pagination::perPage())->withQueryString();
        return view('panitia.seleksi.index', compact('pendaftars'));
    }

    public function decide(Request $request, Pendaftar $pendaftar)
    {
        $allowedMajors = collect([$pendaftar->major_choice_1, $pendaftar->major_choice_2])->filter()->unique()->all();

        $validated = $request->validate([
            'status'   => 'required|in:accepted,rejected',
            'major_id' => ['nullable', 'required_if:status,accepted', Rule::in($allowedMajors)],
            'notes'    => 'nullable|string|max:500',
        ], [
            'major_id.required_if' => 'Pilih jurusan yang disetujui sebelum menerima siswa.',
            'major_id.in' => 'Jurusan yang disetujui harus berasal dari pilihan siswa.',
        ]);

        $oldStatus = $pendaftar->registration_status;

        // Update pendaftar status
        $pendaftar->update([
            'registration_status' => $validated['status'],
        ]);

        // Buat hasil seleksi
        HasilSeleksi::updateOrCreate(
            ['applicant_id' => $pendaftar->id],
            [
                'major_id'          => $validated['status'] === 'accepted' ? $validated['major_id'] : null,
                'status'            => $validated['status'],
                'announcement_date' => now(),
                'notes'             => $validated['notes'] ?? null,
                'decided_by'        => Auth::id(),
                'decided_at'        => now(),
            ]
        );

        if ($validated['status'] === 'accepted') {
            ReRegistrationFee::ensureBill($pendaftar->refresh());
        }

        // Log riwayat status
        RiwayatStatusPendaftar::create([
            'pendaftar_id' => $pendaftar->id,
            'status_lama'  => $oldStatus,
            'status_baru'  => $validated['status'],
            'catatan'      => $validated['notes'] ?? null,
            'diubah_oleh'  => Auth::id(),
        ]);

        $msg = 'Keputusan disimpan.';

        return redirect()->route($this->routeName('seleksi.index'))
                         ->with('success', $msg);
    }

    private function routeName(string $name): string
    {
        return (request()->routeIs('admin.*') ? 'admin.' : 'panitia.') . $name;
    }
}
