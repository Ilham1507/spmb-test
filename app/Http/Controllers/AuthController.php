<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['login' => ['required', 'string'], 'password' => ['required', 'string']]);
        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        if (!Auth::attempt([$field => $data['login'], 'password' => $data['password']], $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'Email/nomor WhatsApp atau kata sandi tidak tepat.'])->onlyInput('login');
        }
        $request->session()->regenerate();
        return redirect()->intended(route('payments.index'));
    }

    public function register(Request $request)
    {
        $data = $request->validate(['name' => ['required','string','max:255'], 'email' => ['nullable','email','unique:users'], 'phone' => ['required','string','unique:users'], 'password' => ['required','min:8','confirmed']]);
        $user = User::create([...$data, 'password' => Hash::make($data['password']), 'role' => 'siswa', 'account_activated_at' => now()]);
        Siswa::create(['user_id' => $user->id, 'nama' => $user->name, 'nis' => 'DAFTAR-'.strtoupper(Str::random(8)), 'kelas' => 'Calon Siswa', 'jurusan' => '-', 'phone' => $user->phone, 'email' => $user->email]);
        Auth::login($user);
        return redirect()->route('payments.index');
    }

    public function activate(Request $request, Siswa $siswa)
    {
        abort_unless($request->hasValidSignature(), 403, 'Tautan aktivasi sudah kedaluwarsa. Silakan masuk menggunakan akun Anda.');
        $data = $request->validate(['password' => ['required','min:8','confirmed']]);
        $user = $siswa->user ?: User::create(['name' => $siswa->nama, 'email' => $siswa->email, 'phone' => $siswa->phone, 'password' => Hash::make($data['password']), 'role' => 'siswa']);
        if (!$siswa->user_id) $siswa->update(['user_id' => $user->id]);
        $user->update(['password' => Hash::make($data['password']), 'account_activated_at' => now()]);
        Auth::login($user);
        return redirect()->route('payments.index');
    }

    public function showActivation(Request $request, Siswa $siswa)
    {
        if (!$request->hasValidSignature()) return redirect()->route('login')->withErrors(['login' => 'Tautan sudah tidak berlaku. Silakan masuk untuk melanjutkan.']);
        return view('auth.activate', compact('siswa'));
    }

    public function logout(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('login'); }
}
