@extends('layouts.auth', ['title' => 'Daftar'])
@section('content')
    <div class="brand"><div class="brand-mark">SM</div><div class="brand-copy"><strong>SMK Negeri</strong><span>Portal Siswa</span></div></div>
    <h1>Buat akun<br>barumu.</h1>
    <p class="intro">Daftar sekali, lalu akses semua kebutuhan sekolahmu.</p>
    @if($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('register.submit') }}">
        @csrf
        <div class="field"><label class="field-label" for="name">Nama lengkap</label><div class="input-wrap"><input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Nama lengkapmu" autocomplete="name" required></div></div>
        <div class="field"><label class="field-label" for="phone">Nomor WhatsApp</label><div class="input-wrap"><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" required></div></div>
        <div class="field"><label class="field-label" for="email">Email (opsional)</label><div class="input-wrap"><input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email"></div></div>
        <div class="field"><label class="field-label" for="register-password">Kata sandi</label><div class="input-wrap"><input id="register-password" name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="new-password" required><button type="button" class="password-toggle" data-password-toggle="register-password" aria-label="Tampilkan kata sandi"><svg class="eye" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.2"/></svg><svg class="eye-off" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="m3 3 18 18M10.6 6.9A10.6 10.6 0 0 1 12 7c6 0 9.5 5 9.5 5a16 16 0 0 1-3.1 3.5M6.2 6.2C3.8 7.5 2.5 12 2.5 12s3.5 5 9.5 5a9.7 9.7 0 0 0 3.2-.5"/></svg></button></div></div>
        <div class="field"><label class="field-label" for="password_confirmation">Ulangi kata sandi</label><div class="input-wrap"><input id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi kata sandi" autocomplete="new-password" required></div></div>
        <button class="submit" type="submit">Buat akun <span aria-hidden="true">→</span></button>
    </form>
    <p class="switch">Sudah punya akun? <a class="link" href="{{ route('login') }}">Masuk di sini</a></p>
@endsection
