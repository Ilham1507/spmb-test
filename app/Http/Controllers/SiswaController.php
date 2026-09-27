<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $siswas = Siswa::latest()->get();

        return view('siswa.index', compact('siswas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('siswa.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Siswa::create($this->validated($request));

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Siswa $siswa)
    {
        return redirect()->route('siswa.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Siswa $siswa)
    {
        return redirect()->route('siswa.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Siswa $siswa)
    {
        $siswa->update($this->validated($request, $siswa));

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Siswa $siswa)
    {
        $siswa->delete();

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    private function validated(Request $request, ?Siswa $siswa = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'nis' => ['required', 'string', 'max:20', 'unique:siswas,nis'.($siswa ? ','.$siswa->id : '')],
            'kelas' => ['required', 'string', 'max:20'],
            'jurusan' => ['required', 'string', 'max:50'],
        ]);
    }
}
