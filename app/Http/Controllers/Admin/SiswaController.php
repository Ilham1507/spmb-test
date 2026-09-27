<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Support\Pagination;
use App\Services\WhatsappCloudApiService;
use App\Services\PhoneChangeVerificationService;
use Throwable;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $students = Pendaftar::with(['user', 'biodata', 'alamat', 'jurusan1', 'kontak'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('registration_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($user) use ($search) {
                            $user->where('phone', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('biodata', function ($biodata) use ($search) {
                            $biodata->where('full_name', 'like', "%{$search}%")
                                ->orWhere('nisn', 'like', "%{$search}%")
                                ->orWhere('nik', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(Pagination::perPage())->withQueryString();

        return view('admin.siswa.index', compact('students'));
    }

    public function updateLogin(Request $request, Pendaftar $pendaftar, WhatsappCloudApiService $whatsapp, PhoneChangeVerificationService $phoneChanges)
    {
        abort_if(!$pendaftar->user, 404);

        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);

        $validated = $request->validate([
            'phone' => ['required', 'regex:/^08[0-9]{8,13}$/', Rule::unique('pengguna', 'phone')->ignore($pendaftar->user->id)],
            'password' => ['nullable', 'string', 'min:8'],
        ], [
            'phone.required' => 'Nomor WhatsApp siswa wajib diisi.',
            'phone.regex' => 'Nomor WhatsApp harus diawali 08 dan berisi 10 sampai 15 digit.',
            'phone.unique' => 'Nomor WhatsApp sudah digunakan akun lain.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
        ]);

        $data = [];
        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $phoneChanged = $validated['phone'] !== $pendaftar->user->phone;
        $pendaftar->user->update($data);
        if ($phoneChanged) {
            try {
                $phoneChanges->send($pendaftar->user, $validated['phone'], $whatsapp);
            } catch (Throwable $exception) {
                report($exception);
                return back()->with('warning', 'Kata sandi diperbarui, tetapi kode nomor baru belum dapat dikirim. Nomor lama tetap aktif.');
            }
        }

        return back()->with('success', $phoneChanged ? 'Login diperbarui. Nomor baru menunggu verifikasi siswa.' : 'Login siswa berhasil diperbarui.');
    }

    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        return str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
    }
}
