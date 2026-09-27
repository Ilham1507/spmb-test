<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\JadwalSpmb;
use App\Models\Pendaftar;
use Illuminate\Http\Request;

class DaftarHadirTesController extends Controller
{
    /**
     * Roster read-only for staff. A participant appears in the session they selected
     * when submitting their registration form.
     */
    public function index(Request $request)
    {
        $schedules = JadwalSpmb::query()
            ->where('available_for_student_selection', true)
            ->orderBy('tanggal_mulai')
            ->get();

        $selectedScheduleId = $request->integer('jadwal');
        if ($selectedScheduleId && ! $schedules->contains('id', $selectedScheduleId)) {
            $selectedScheduleId = 0;
        }

        $participants = Pendaftar::query()
            ->with(['biodata', 'sekolahAsal', 'jurusan1', 'preferredTestSchedule'])
            ->whereNotNull('preferred_test_schedule_id')
            ->whereIn('registration_status', ['submitted', 'verified', 'accepted', 're_registered'])
            ->when($selectedScheduleId, fn ($query) => $query->where('preferred_test_schedule_id', $selectedScheduleId))
            ->latest('preferred_test_selected_at')
            ->get()
            ->groupBy('preferred_test_schedule_id');

        return view('shared.daftar-hadir-tes.index', compact('schedules', 'participants', 'selectedScheduleId'));
    }
}
