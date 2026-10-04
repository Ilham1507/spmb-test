@extends('layouts.admin')

@section('title', 'Kelola Pengguna')
@section('page_title', 'Kelola Pengguna')
@section('page_description', 'Kelola akun petugas yang membantu operasional dan keuangan SPMB.')

@section('content')
<div x-data="{ addOpen: {{ old('_modal') === 'add' ? 'true' : 'false' }}, editId: {{ old('_modal') === 'edit' ? (int) old('user_id') : 'null' }}, roleId: {{ old('_modal') === 'role' ? (int) old('user_id') : 'null' }} }">
    <section class="admin-card overflow-hidden bg-white p-0">
        <div class="admin-card-header border-b border-slate-100">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-black text-slate-950">Daftar Pengguna Sistem</h2>
                    <span class="admin-count-badge bg-slate-100 text-slate-600">{{ $users->total() }} akun</span>
                    <span class="admin-count-badge bg-emerald-50 text-emerald-700">{{ $teacherCount }} staf operasional</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">Daftar sendiri dari halaman depan tetap menjadi siswa. Admin dapat mengubah peran akun di sini tanpa membuat akun baru. Guru menggunakan akses Panitia.</p>
            </div>
            <button type="button" @click="addOpen=true" class="admin-primary-button bg-emerald-600 hover:bg-emerald-700">+ Tambah User</button>
        </div>

        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex min-w-0 flex-1 flex-col gap-2 md:flex-row">
                <x-list-search placeholder="Cari nama atau nomor WhatsApp pengguna" class="min-w-0 flex-1" />
                <button type="submit" class="admin-primary-button bg-blue-700 hover:bg-blue-800">Cari</button>
                @if(request('search'))
                    <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-[42px] items-center justify-center rounded-xl bg-slate-100 px-4 text-sm font-black text-slate-600 hover:bg-slate-200">Reset</a>
                @endif
            </form>
            <x-per-page-pagination :paginator="$users" toolbar />
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table min-w-[780px]">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>WhatsApp Login</th>
                        <th>Akses</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        @php
                            $roleName = $user->role?->name ?? '-';
                            $roleLabel = match ($roleName) {
                                'panitia' => 'Panitia / Guru',
                                'bendahara' => 'Bendahara',
                                'kepala_sekolah' => 'Kepala Sekolah',
                                'admin' => 'Admin',
                                'peserta' => 'Siswa',
                                default => ucfirst($roleName),
                            };
                            $roleClass = match ($roleName) {
                                'admin' => 'bg-emerald-100 text-emerald-700',
                                'panitia' => 'bg-sky-100 text-sky-700',
                                'bendahara' => 'bg-amber-100 text-amber-800',
                                'kepala_sekolah' => 'bg-violet-100 text-violet-700',
                                'peserta' => 'bg-slate-100 text-slate-600',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <tr>
                            <td><div class="flex items-center gap-3"><x-user-avatar :user="$user" size="h-10 w-10" class="border border-slate-200" /><div><div class="font-black text-slate-950">{{ $user->name }}</div><div class="text-xs font-semibold text-slate-400">ID {{ $user->id }}</div></div></div></td>
                            <td>
                                <span class="font-bold text-slate-700">{{ $user->phone ?: '-' }}</span>
                                @if(!$user->phone)<p class="mt-1 text-[10px] font-bold text-amber-600">Belum bisa login dengan WhatsApp</p>@endif
                            </td>
                            <td><span class="rounded-full px-2.5 py-1 text-xs font-black {{ $roleClass }}">{{ $roleLabel }}</span></td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="roleId={{ $user->id }}" class="rounded-xl bg-violet-50 px-3 py-2 text-xs font-black text-violet-700">Ubah Peran</button>
                                    @if(in_array($roleName, ['panitia', 'bendahara', 'kepala_sekolah'], true))
                                    <button type="button" @click="editId={{ $user->id }}" class="rounded-xl bg-sky-50 px-3 py-2 text-xs font-black text-sky-700 hover:bg-sky-100">Edit</button>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus user {{ $user->name }}? Aksi ini tidak bisa dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-black text-rose-700 hover:bg-rose-100">Hapus</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </section>

    <template x-teleport="body"><div x-cloak x-show="addOpen" x-transition.opacity class="fixed inset-0 z-[2147483000] flex items-center justify-center bg-slate-950/45 p-3 sm:p-4 backdrop-blur-sm" @keydown.escape.window="addOpen=false">
        <div @click.outside="addOpen=false" class="user-account-dialog flex max-h-[calc(100dvh-24px)] w-full max-w-xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl sm:max-h-[calc(100dvh-32px)]">
            <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
                <div>
                    <h3 class="text-lg font-black text-slate-950">Tambah User</h3>
                    <p class="mt-1 text-xs text-slate-500">Buat akun panitia, bendahara, atau kepala sekolah sesuai tugasnya.</p>
                </div>
                <button type="button" @click="addOpen=false" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.users.guru.store') }}" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <input type="hidden" name="_modal" value="add">
                <div class="admin-form-grid min-h-0 flex-1 overflow-y-auto p-5">
                <div>
                    <label class="admin-label">Nama Lengkap *</label>
                    <input name="name" value="{{ old('name') }}" required class="admin-input" placeholder="Nama petugas">
                    @error('name')<p class="admin-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Nomor WhatsApp *</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required inputmode="numeric" pattern="[0-9+ ]*" class="admin-input" placeholder="08xxxxxxxxxx">
                    <p class="admin-help">Dipakai untuk login staf.</p>
                    @error('phone')<p class="admin-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <x-form-select name="role" label="Akses" :required="true" :options="['panitia' => 'Panitia', 'bendahara' => 'Bendahara', 'kepala_sekolah' => 'Kepala Sekolah']" :value="old('role', 'panitia')" placeholder="Pilih akses" :menu-z-index="2147483500" />
                    @error('role')<p class="admin-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="admin-label">Kata Sandi Awal *</label>
                    <input type="password" name="password" required minlength="8" class="admin-input" placeholder="Minimal 8 karakter">
                    @error('password')<p class="admin-error">{{ $message }}</p>@enderror
                </div>

                </div><div class="flex shrink-0 justify-end gap-2 border-t border-slate-100 bg-white px-4 py-3 sm:px-5">
                    <button type="button" @click="addOpen=false" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-black text-slate-600">Batal</button>
                    <button class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-black text-white">Simpan</button>
                </div>
            </form>
        </div>
    </div></template>

    @foreach($users as $user)
        <template x-teleport="body"><div x-cloak x-show="roleId==={{ $user->id }}" class="fixed inset-0 z-[2147483000] flex items-center justify-center bg-slate-950/45 p-4" @keydown.escape.window="roleId=null">
            <form method="POST" action="{{ route('admin.users.role.update', $user) }}" class="user-account-dialog w-full max-w-lg rounded-3xl bg-white p-5 shadow-2xl" @click.outside="roleId=null" onsubmit="return confirm('Ubah akses akun ini? Peran Admin memberikan akses penuh ke sistem.')">
                @csrf @method('PATCH')
                <input type="hidden" name="_modal" value="role">
                <input type="hidden" name="user_id" value="{{ $user->id }}">
                <h3 class="text-lg font-black">Ubah Peran</h3>
                <p class="my-3 text-sm">{{ $user->name }} · {{ $user->phone }}</p>
                <x-form-select name="role" label="Peran pengguna" :required="true" :options="$roleOptions" :value="old('user_id') == $user->id ? old('role', $user->role?->name) : $user->role?->name" :menu-z-index="2147483500" />
                @if(old('user_id') == $user->id) @error('role')<p class="admin-error">{{ $message }}</p>@enderror @endif
                <p class="mt-3 text-xs text-slate-500">Nomor login, kata sandi, serta riwayat pendaftaran dan pembayaran tetap tersimpan. Pastikan akun ini benar milik petugas sebelum memberi akses.</p>
                <div class="mt-5 flex justify-end gap-2"><button type="button" @click="roleId=null" class="rounded-xl border px-4 py-2">Batal</button><button class="rounded-xl bg-violet-700 px-4 py-2 font-bold text-white">Simpan Peran</button></div>
            </form>
        </div></template>
        <template x-teleport="body"><div x-cloak x-show="editId==={{ $user->id }}" x-transition.opacity class="fixed inset-0 z-[2147483000] flex items-center justify-center bg-slate-950/45 p-3 sm:p-4 backdrop-blur-sm" @keydown.escape.window="editId=null">
            <div @click.outside="editId=null" class="user-account-dialog flex max-h-[calc(100dvh-24px)] w-full max-w-xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl sm:max-h-[calc(100dvh-32px)]">
                <div class="shrink-0 flex items-start justify-between border-b border-slate-100 p-5">
                    <div>
                        <h3 class="text-lg font-black text-slate-950">Edit User</h3>
                        <p class="mt-1 text-xs text-slate-500">Ubah data login dan akses {{ $user->name }}.</p>
                    </div>
                    <button type="button" @click="editId=null" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">&times;</button>
                </div>

                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_modal" value="edit">
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    <div class="admin-form-grid min-h-0 flex-1 overflow-y-auto p-5">

                    <div>
                        <label class="admin-label">Nama Lengkap *</label>
                        <input name="name" value="{{ old('user_id') == $user->id ? old('name', $user->name) : $user->name }}" required class="admin-input" placeholder="Nama petugas">
                        @if(old('user_id') == $user->id) @error('name')<p class="admin-error">{{ $message }}</p>@enderror @endif
                    </div>

                    <div>
                        <label class="admin-label">Nomor WhatsApp *</label>
                        <input type="tel" name="phone" value="{{ old('user_id') == $user->id ? old('phone', $user->phone) : $user->phone }}" required inputmode="numeric" pattern="[0-9+ ]*" class="admin-input" placeholder="08xxxxxxxxxx">
                        @if(old('user_id') == $user->id) @error('phone')<p class="admin-error">{{ $message }}</p>@enderror @endif
                    </div>

                    <div>
                        @php $selectedRole = old('user_id') == $user->id ? old('role', $user->role?->name) : $user->role?->name; @endphp
                        <x-form-select name="role" label="Akses" :required="true" :options="['panitia' => 'Panitia', 'bendahara' => 'Bendahara', 'kepala_sekolah' => 'Kepala Sekolah']" :value="$selectedRole" placeholder="Pilih akses" :menu-z-index="2147483500" />
                        @if(old('user_id') == $user->id) @error('role')<p class="admin-error">{{ $message }}</p>@enderror @endif
                    </div>

                    <div>
                        <label class="admin-label">Reset Kata Sandi <span class="font-medium text-slate-400">(opsional)</span></label>
                        <input type="password" name="password" minlength="8" class="admin-input" placeholder="Kosongkan jika tidak diganti">
                        @if(old('user_id') == $user->id) @error('password')<p class="admin-error">{{ $message }}</p>@enderror @endif
                    </div>

                    </div><div class="flex shrink-0 justify-end gap-2 border-t border-slate-100 bg-white px-4 py-3 sm:px-5">
                        <button type="button" @click="editId=null" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-black text-slate-600">Batal</button>
                        <button class="rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-black text-white">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div></template>
    @endforeach
</div>
@endsection
