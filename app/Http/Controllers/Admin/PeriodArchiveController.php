<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TahunAjaran;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PeriodArchiveController extends Controller
{
    public function index(Request $request)
    {
        $activeYear = TahunAjaran::query()
            ->withCount('pendaftars')
            ->where('is_active', true)
            ->first();

        $baseQuery = TahunAjaran::query()->withCount('pendaftars');
        if ($search = trim((string) $request->input('search'))) {
            $baseQuery->where('name', 'like', "%{$search}%");
        }

        $years = $baseQuery
            ->latest('start_date')
            ->paginate(Pagination::perPage())
            ->withQueryString();

        $summary = [
            'periods' => TahunAjaran::count(),
            'archived' => TahunAjaran::where('is_archived', true)->count(),
            'active_applicants' => $activeYear?->pendaftars_count ?? 0,
        ];

        return view('admin.period-archive.index', compact('years', 'activeYear', 'summary'));
    }

    public function archive(TahunAjaran $tahunAjaran)
    {
        if ($tahunAjaran->is_active) {
            return back()->with('warning', 'Tahun ajaran aktif tidak dapat diarsipkan. Aktifkan periode baru terlebih dahulu.');
        }

        $tahunAjaran->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => Auth::id(),
        ]);

        return back()->with('success', 'Periode '.$tahunAjaran->name.' sudah diarsipkan dan data operasionalnya dikunci.');
    }

    public function restore(TahunAjaran $tahunAjaran)
    {
        $tahunAjaran->update([
            'is_archived' => false,
            'archived_at' => null,
            'archived_by' => null,
        ]);

        return back()->with('success', 'Arsip periode '.$tahunAjaran->name.' dibuka kembali.');
    }
}
