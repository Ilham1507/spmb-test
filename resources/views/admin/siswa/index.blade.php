@extends('layouts.admin')

@section('title', 'Login Siswa')
@section('page_title', 'Data Siswa')
@section('page_description', 'Kelola akun login siswa dan cek identitas pendaftaran.')

@section('content')
<div x-data="{ editId: null }" class="space-y-4">
    <section class="admin-card bg-white">
        <form method="GET" action="{{ route('admin.siswa.index') }}" class="flex flex-col gap-2 md:flex-row">
            <x-list-search placeholder="Cari nama, WhatsApp, NISN, NIK, atau nomor pendaftaran" class="min-w-0 flex-1" />
            <button type="submit" class="admin-primary-button bg-emerald-600 hover:bg-emerald-700">Cari</button>
            @if(request('search'))
                <a href="{{ route('admin.siswa.index') }}" class="inline-flex min-h-[42px] items-center justify-center rounded-xl bg-slate-100 px-4 text-sm font-black text-slate-600 hover:bg-slate-200">Reset</a>
            @endif
        </form>
    </section>

    <section class="admin-card overflow-hidden bg-white p-0">
        <div class="admin-card-header border-b border-slate-100">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-black text-slate-950">Akun Login Siswa</h2>
                    <span class="admin-count-badge bg-slate-100 text-slate-600">{{ $students->total() }} siswa</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Siswa login memakai nomor WhatsApp aktif. Password bisa direset admin jika siswa lupa.</p>
            </div>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table min-w-[920px]">
                <thead>
                    <tr>
                        <th>No. Pendaftaran</th>
                        <th>Nama Siswa</th>
                        <th>WhatsApp Login</th>
                        <th>NISN / NIK</th>
                        <th>Jurusan</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            $statusLabels = [
                                'draft' => 'Draft',
                                'submitted' => 'Pending',
                                'verified' => 'Approved',
                                'accepted' => 'Diterima',
                                'rejected' => 'Ditolak',
                                're_registered' => 'Daftar Ulang',
                            ];
                            $statusClass = match ($student->registration_status) {
                                'verified', 'accepted', 're_registered' => 'bg-emerald-100 text-emerald-700',
                                'submitted' => 'bg-amber-100 text-amber-800',
                                'rejected' => 'bg-rose-100 text-rose-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <tr>
                            <td class="font-mono text-xs font-black text-emerald-700">{{ $student->registration_number ?? 'Belum dibuat' }}</td>
                            <td>
                                <div class="font-black text-slate-950">{{ $student->biodata?->full_name ?? $student->user?->name ?? '-' }}</div>
                                <div class="text-xs font-semibold text-slate-400">Akun ID {{ $student->user?->id ?? '-' }}</div>
                            </td>
                            <td>
                                @if($student->user?->phone)
                                    <span class="font-bold text-slate-700">{{ $student->user->phone }}</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-black text-amber-700">Belum ada WA</span>
                                @endif
                            </td>
                            <td class="text-xs font-semibold text-slate-500">
                                <div>NISN: {{ $student->biodata?->nisn ?? '-' }}</div>
                                <div>NIK: {{ $student->biodata?->nik ?? '-' }}</div>
                            </td>
                            <td>{{ $student->jurusan1?->name ?? '-' }}</td>
                            <td><span class="rounded-full px-2.5 py-1 text-xs font-black {{ $statusClass }}">{{ $statusLabels[$student->registration_status] ?? ucfirst(str_replace('_', ' ', $student->registration_status ?? '-')) }}</span></td>
                            <td class="text-right">
                                <button type="button" @click="editId={{ $student->id }}" class="rounded-xl bg-sky-50 px-3 py-2 text-xs font-black text-sky-700 hover:bg-sky-100">Kelola Login</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-sm font-semibold text-slate-400">Belum ada data siswa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-per-page-pagination :paginator="$students" />
    </section>

    @foreach($students as $student)
        <div x-cloak x-show="editId==={{ $student->id }}" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm" @keydown.escape.window="editId=null">
            <div @click.outside="editId=null" class="max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-3xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-100 p-5">
                    <div>
                        <h3 class="text-lg font-black text-slate-950">Kelola Login Siswa</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ $student->biodata?->full_name ?? $student->user?->name ?? 'Siswa' }}</p>
                    </div>
                    <button type="button" @click="editId=null" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">&times;</button>
                </div>

                <form method="POST" action="{{ route('admin.siswa.login.update', $student) }}" class="admin-form-grid p-5">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="admin-label">Nomor WhatsApp Login *</label>
                        <input type="tel" name="phone" value="{{ old('phone', $student->user?->phone) }}" required inputmode="numeric" pattern="[0-9+ ]*" class="admin-input" placeholder="08xxxxxxxxxx">
                        <p class="admin-help">Nomor ini dipakai siswa untuk login.</p>
                        @error('phone')<p class="admin-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="admin-label">Reset Kata Sandi <span class="font-medium text-slate-400">(opsional)</span></label>
                        <input type="password" name="password" minlength="8" class="admin-input" placeholder="Isi hanya jika ingin ganti password">
                        <p class="admin-help">Kosongkan jika password lama tetap dipakai.</p>
                        @error('password')<p class="admin-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-3 text-xs font-semibold text-slate-500">
                        <div>No. Pendaftaran: <span class="font-black text-slate-800">{{ $student->registration_number ?? 'Belum dibuat' }}</span></div>
                        <div>NISN: <span class="font-black text-slate-800">{{ $student->biodata?->nisn ?? '-' }}</span></div>
                        <div>NIK: <span class="font-black text-slate-800">{{ $student->biodata?->nik ?? '-' }}</span></div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button type="button" @click="editId=null" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600">Batal</button>
                        <button class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Simpan Login</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
