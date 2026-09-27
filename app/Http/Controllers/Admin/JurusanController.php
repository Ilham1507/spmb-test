<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Jurusan;
use App\Models\Pendaftar;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\Pagination;

class JurusanController extends Controller
{
    public function index()
    {
        $jurusans = Jurusan::latest()->paginate(Pagination::perPage())->withQueryString();
        return view('admin.jurusan.index', compact('jurusans'));
    }

    public function create()
    {
        return view('admin.jurusan.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'quota' => 'required|integer|min:0',
            'status' => 'required|in:aktif,nonaktif',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);
        unset($data['logo']);
        $data['code'] = $this->generateCode($data['name']);
        if ($request->hasFile('logo')) $data['logo_path'] = 'storage/'.$request->file('logo')->store('jurusan', 'public');
        Jurusan::create($data);

        return redirect()->route('admin.jurusan.index')->with('success', 'Jurusan berhasil ditambahkan.');
    }

    public function edit(Jurusan $jurusan)
    {
        return view('admin.jurusan.edit', compact('jurusan'));
    }

    public function update(Request $request, Jurusan $jurusan)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'quota' => 'required|integer|min:0',
            'status' => 'required|in:aktif,nonaktif',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);
        unset($data['logo']);
        $data['code'] = $this->generateCode($data['name'], $jurusan->id);
        if ($request->hasFile('logo')) {
            if ($jurusan->logo_path && str_starts_with($jurusan->logo_path, 'storage/')) Storage::disk('public')->delete(substr($jurusan->logo_path, 8));
            $data['logo_path'] = 'storage/'.$request->file('logo')->store('jurusan', 'public');
        }
        $jurusan->update($data);

        return redirect()->route('admin.jurusan.index')->with('success', 'Jurusan berhasil diperbarui di seluruh sistem.');
    }

    public function destroy(Jurusan $jurusan)
    {
        $dipakai = Pendaftar::query()->where('major_choice_1', $jurusan->id)->orWhere('major_choice_2', $jurusan->id)->exists();
        if ($dipakai) return back()->with('warning', 'Jurusan sudah tercatat pada data siswa. Ubah status menjadi nonaktif agar riwayat siswa tetap aman.');
        if ($jurusan->logo_path && str_starts_with($jurusan->logo_path, 'storage/')) Storage::disk('public')->delete(substr($jurusan->logo_path, 8));
        $jurusan->delete();
        return redirect()->route('admin.jurusan.index')->with('success', 'Jurusan berhasil dihapus.');
    }

    private function generateCode(string $name, ?int $ignoreId = null): string
    {
        $base = strtoupper(substr(Str::slug($name, ''), 0, 14)) ?: 'JURUSAN';
        $code = $base;
        $index = 2;

        while (Jurusan::where('code', $code)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $suffix = (string) $index++;
            $code = substr($base, 0, 20 - strlen($suffix)) . $suffix;
        }

        return $code;
    }
}
