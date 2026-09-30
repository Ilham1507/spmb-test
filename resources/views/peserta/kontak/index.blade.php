@extends('layouts.peserta')

@section('title', 'Data Kontak')
@section('page_title', 'Data Kontak')

@section('content')
<div class="mx-auto max-w-7xl">
    <x-step-indicator currentStep="7" />
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 md:p-8">
        <h2 class="mb-1 text-xl font-bold text-slate-800">Data Kontak</h2>
        <p class="mb-6 text-sm text-slate-500">Nomor WhatsApp digunakan untuk masuk. Email wajib diisi dan diverifikasi sebelum melanjutkan ke tahap berikutnya.</p>
        <form method="POST" action="{{ route('peserta.kontak') }}" class="space-y-5">
            @csrf
            @if($enabledFields->has('no_handphone'))
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-500">No. HP / WhatsApp</label>
                    <input type="text" value="{{ auth()->user()->phone }}" readonly class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700">
                    <p class="mt-1 text-xs text-slate-400">Otomatis mengikuti nomor WhatsApp untuk login.</p>
                </div>
            @endif
            @php($emailKey = $enabledFields->search(fn ($label) => strtolower($label) === 'email'))
            @if($emailKey !== false)
                <div>
                    <label for="email" class="mb-1.5 block text-xs font-semibold text-slate-500">Email <span class="text-rose-500">*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email', $pendaftar?->kontak?->email ?? '') }}" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/40" placeholder="nama@email.com">
                    <p class="mt-1 text-xs text-slate-400">Tautan verifikasi akan dikirim ke alamat email ini.</p>
                    @error('email') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    @if($pendaftar?->kontak?->email && !$pendaftar->kontak->email_verified_at)
                        <p class="mt-2 text-xs font-semibold text-amber-700">Email belum terverifikasi. Buka tautan yang dikirim ke email Anda untuk melanjutkan.</p>
                        <button type="submit" name="action" value="send_verification" class="mt-3 inline-flex items-center gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-2.5 text-sm font-black text-teal-800 transition hover:bg-teal-100">
                            Kirim verifikasi email
                            <span aria-hidden="true">→</span>
                        </button>
                    @elseif($pendaftar?->kontak?->email_verified_at)
                        <p class="mt-2 text-xs font-semibold text-emerald-700">Email sudah terverifikasi.</p>
                    @endif
                </div>
            @endif
            <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('peserta.dashboard') }}" class="btn-link-back">Kembali ke Dashboard</a>
                <button type="submit" class="btn-primary">Simpan &amp; Lanjut <span aria-hidden="true">→</span></button>
            </div>
        </form>
    </div>
</div>
@endsection
