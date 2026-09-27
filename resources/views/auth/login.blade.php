@extends('layouts.auth', ['title' => 'Masuk'])
@section('content')
    <div class="brand"><div class="brand-mark">SM</div><div class="brand-copy"><strong>SMK Negeri</strong><span>Portal Siswa</span></div></div>
    <h1>Selamat datang<br>kembali.</h1>
    <p class="intro">Masuk untuk melanjutkan ke portal sekolahmu.</p>
    @if($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('login.submit') }}">
        @csrf
        <div class="field"><label class="field-label" for="login">Email atau nomor WhatsApp</label><div class="input-wrap"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v8a2.5 2.5 0 0 1-2.5 2.5H10l-4.5 4v-4.4A2.5 2.5 0 0 1 4 13.2v-7.7Z"/><path d="M8 8h8M8 11h5"/></svg><input id="login" name="login" type="text" value="{{ old('login') }}" placeholder="08xxxxxxxxxx atau email" autocomplete="username" required></div></div>
        <div class="field"><label class="field-label" for="password">Kata sandi</label><div class="input-wrap"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><input id="password" name="password" type="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required><button type="button" class="password-toggle" data-password-toggle="password" aria-label="Tampilkan kata sandi"><svg class="eye" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.2"/></svg><svg class="eye-off" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="m3 3 18 18M10.6 6.9A10.6 10.6 0 0 1 12 7c6 0 9.5 5 9.5 5a16 16 0 0 1-3.1 3.5M6.2 6.2C3.8 7.5 2.5 12 2.5 12s3.5 5 9.5 5a9.7 9.7 0 0 0 3.2-.5"/></svg></button></div></div>
        <div class="field-row"><label class="check"><input type="checkbox" name="remember"> Ingat saya</label><a class="link" href="#">Lupa kata sandi?</a></div>
        <button class="submit" type="submit">Masuk ke portal <span aria-hidden="true">→</span></button>
    </form>
    <p class="switch">Belum punya akun? <a class="link" href="{{ route('register') }}">Daftar sekarang</a></p>
@endsection
