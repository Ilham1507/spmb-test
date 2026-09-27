<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterDropdownController extends Controller
{
    private array $tables = ['agama' => 'Agama', 'pekerjaan' => 'Pekerjaan', 'pendidikan' => 'Pendidikan', 'penghasilan' => 'Penghasilan', 'jalur_pendaftaran' => 'Jalur Pendaftaran'];

    public function index()
    {
        $data = collect($this->tables)->mapWithKeys(fn ($label, $table) => [$table => DB::table($table)->orderBy($table === 'jalur_pendaftaran' ? 'name' : 'nama')->get()]);
        return view('admin.master.dropdown', ['tables' => $this->tables, 'data' => $data]);
    }

    public function page(string $table)
    {
        abort_unless(array_key_exists($table, $this->tables), 404);
        $column = $table === 'jalur_pendaftaran' ? 'name' : 'nama';
        $data = collect([$table => DB::table($table)->orderBy($column)->get()]);
        return view('admin.master.dropdown', [
            'tables' => [$table => $this->tables[$table]],
            'data' => $data,
            'pageLabel' => $this->tables[$table],
        ]);
    }

    public function store(Request $request, string $table)
    {
        abort_unless(array_key_exists($table, $this->tables), 404);
        $column = $table === 'jalur_pendaftaran' ? 'name' : 'nama';
        $request->validate([$column => 'required|string|max:150']);
        DB::table($table)->insert([$column => $request->input($column)] + ($table === 'jalur_pendaftaran' ? ['status' => 'aktif', 'quota' => 0] : []));
        return back()->with('success', $this->tables[$table] . ' berhasil ditambahkan.');
    }

    public function destroy(string $table, int $id)
    {
        abort_unless(array_key_exists($table, $this->tables), 404);
        if ($table === 'jalur_pendaftaran') DB::table($table)->where('id', $id)->update(['status' => 'nonaktif']);
        else DB::table($table)->where('id', $id)->delete();
        return back()->with('success', $this->tables[$table] . ' berhasil diperbarui.');
    }

    public function update(Request $request, string $table, int $id)
    {
        abort_unless(array_key_exists($table, $this->tables), 404);
        $column = $table === 'jalur_pendaftaran' ? 'name' : 'nama';
        $request->validate([$column => 'required|string|max:150']);
        DB::table($table)->where('id', $id)->update([$column => $request->input($column)]);
        return back()->with('success', $this->tables[$table] . ' berhasil diubah.');
    }
}
