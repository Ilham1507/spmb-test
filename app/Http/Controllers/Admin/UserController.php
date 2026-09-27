<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Support\Pagination;
use App\Services\WhatsappCloudApiService;
use App\Services\PhoneChangeVerificationService;
use Throwable;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['panitia', 'bendahara', 'kepala_sekolah']))
            ->latest('id')
            ->paginate(Pagination::perPage())->withQueryString();
        $teacherCount = User::whereHas('role', fn ($query) => $query->whereIn('name', ['panitia', 'bendahara', 'kepala_sekolah']))->count();

        return view('admin.pengguna.index', compact('users', 'teacherCount'));
    }

    public function storeTeacher(Request $request)
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', 'unique:pengguna,email'],
            'phone' => ['required', 'regex:/^08[0-9]{8,13}$/', 'unique:pengguna,phone'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['panitia', 'bendahara', 'kepala_sekolah'])],
        ], $this->phoneMessages());

        $role = Peran::firstOrCreate(
            ['name' => $validated['role']],
            ['description' => $this->roleDescription($validated['role'])]
        );

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role_id' => $role->id,
        ]);

        return back()->with('success', 'Akun '.$this->roleLabel($validated['role']).' berhasil dibuat.');
    }

    public function update(Request $request, User $user, WhatsappCloudApiService $whatsapp, PhoneChangeVerificationService $phoneChanges)
    {
        $this->ensureManageableStaff($user);

        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('pengguna', 'email')->ignore($user->id)],
            'phone' => ['required', 'regex:/^08[0-9]{8,13}$/', Rule::unique('pengguna', 'phone')->ignore($user->id)],
            'role' => ['required', Rule::in(['panitia', 'bendahara', 'kepala_sekolah'])],
            'password' => ['nullable', 'string', 'min:8'],
        ], $this->phoneMessages() + [
            'name.required' => 'Nama user wajib diisi.',
            'role.required' => 'Akses user wajib dipilih.',
            'role.in' => 'Akses user hanya boleh Panitia, Bendahara, atau Kepala Sekolah.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
        ]);

        $role = Peran::firstOrCreate(
            ['name' => $validated['role']],
            ['description' => $this->roleDescription($validated['role'])]
        );

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'role_id' => $role->id,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $phoneChanged = $validated['phone'] !== $user->phone;
        $user->update($data);
        if ($phoneChanged) {
            try {
                $phoneChanges->send($user, $validated['phone'], $whatsapp);
            } catch (Throwable $exception) {
                report($exception);
                return back()->with('warning', 'Data user diperbarui, tetapi kode nomor WhatsApp baru belum dapat dikirim. Nomor lama tetap aktif.');
            }
        }

        return back()->with('success', $phoneChanged ? 'User diperbarui. Nomor baru menunggu verifikasi pemilik akun.' : 'User berhasil diperbarui.');
    }

    public function updatePhone(Request $request, User $user, WhatsappCloudApiService $whatsapp, PhoneChangeVerificationService $phoneChanges)
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);
        $validated = $request->validate([
            'phone' => ['required', 'regex:/^08[0-9]{8,13}$/', Rule::unique('pengguna', 'phone')->ignore($user->id)],
        ], $this->phoneMessages());

        if ($validated['phone'] === $user->phone) return back()->with('success', 'Nomor WhatsApp tidak berubah.');
        try {
            $phoneChanges->send($user, $validated['phone'], $whatsapp);
        } catch (Throwable $exception) {
            report($exception);
            return back()->with('error', 'Kode verifikasi belum dapat dikirim. Nomor lama tetap aktif.');
        }

        return back()->with('success', "Kode verifikasi sudah dikirim ke nomor baru {$user->name}. Nomor lama tetap aktif sampai pemilik akun mengonfirmasi.");
    }

    public function destroy(User $user)
    {
        $this->ensureManageableStaff($user);

        if (auth()->id() === $user->id) {
            return back()->with('error', 'Akun yang sedang digunakan tidak boleh dihapus.');
        }

        $name = $user->name;
        try {
            $user->delete();
        } catch (QueryException $exception) {
            return back()->with('error', 'User tidak bisa dihapus karena sudah dipakai di riwayat sistem. Ubah datanya saja jika perlu.');
        }

        return back()->with('success', "User {$name} berhasil dihapus.");
    }

    private function ensureManageableStaff(User $user): void
    {
        abort_unless(in_array($user->role?->name, ['panitia', 'bendahara', 'kepala_sekolah'], true), 403);
    }

    private function roleDescription(string $role): string
    {
        return match ($role) {
            'bendahara' => 'Bendahara Keuangan SPMB',
            'kepala_sekolah' => 'Kepala Sekolah',
            default => 'Guru Panitia Piket SPMB',
        };
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'bendahara' => 'bendahara',
            'kepala_sekolah' => 'kepala sekolah',
            default => 'panitia',
        };
    }

    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        return str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
    }

    private function phoneMessages(): array
    {
        return [
            'phone.required' => 'Nomor WhatsApp wajib diisi karena digunakan untuk login.',
            'phone.regex' => 'Nomor WhatsApp harus diawali 08 dan berisi 10 sampai 15 digit.',
            'phone.unique' => 'Nomor WhatsApp sudah digunakan akun lain.',
        ];
    }
}
