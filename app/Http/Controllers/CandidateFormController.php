<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\Request;

class CandidateFormController extends Controller
{
    public function show(Request $request, Siswa $siswa)
    {
        if (!$request->hasValidSignature()) return redirect()->route('login')->withErrors(['login' => 'Tautan sudah tidak berlaku. Silakan masuk untuk melanjutkan.']);
        return view('forms.candidate', compact('siswa'));
    }
    public function store(Request $request, Siswa $siswa)
    {
        if (!$request->hasValidSignature()) return redirect()->route('login')->withErrors(['login' => 'Tautan sudah tidak berlaku. Silakan masuk untuk melanjutkan.']);
        $data = $request->validate(['alamat' => 'required|string|max:1000', 'asal_sekolah' => 'required|string|max:255', 'jurusan_pilihan' => 'required|string|max:100', 'nama_orang_tua' => 'required|string|max:255']);
        $siswa->update(['form_data' => $data, 'jurusan' => $data['jurusan_pilihan']]);
        return redirect()->route('login')->with('success', 'Formulir tersimpan. Silakan masuk ke portal Anda.');
    }
}
