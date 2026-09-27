@php
    $profileLayout = match (auth()->user()?->role?->name) {
        'admin' => 'layouts.admin',
        'panitia' => 'layouts.panitia',
        'bendahara' => 'layouts.bendahara',
        'kepala_sekolah' => 'layouts.kepala-sekolah',
        default => 'layouts.peserta',
    };
@endphp

@extends($profileLayout)

@section('title', 'Profil Saya')
@section('page_title', 'Profil Saya')
@section('page_description', 'Kelola identitas dan keamanan akun.')

@section('content')
    @php
        $profileRole = auth()->user()?->role?->name ?? 'peserta';
        $profileRoleLabel = [
            'admin' => 'Administrator', 'panitia' => 'Panitia SPMB', 'bendahara' => 'Bendahara Keuangan',
            'kepala_sekolah' => 'Kepala Sekolah', 'peserta' => 'Peserta SPMB',
        ][$profileRole] ?? 'Pengguna SPMB';
        $profileTone = [
            'admin' => ['#0b3b83', '#07265d', '#e8f2ff', '#cddff5'],
            'panitia' => ['#6941c6', '#46258e', '#f1ebff', '#ddccff'],
            'bendahara' => ['#b66a0b', '#854600', '#fff4d6', '#f5dfae'],
            'kepala_sekolah' => ['#087a70', '#0b625b', '#e6f5f2', '#cce8e3'],
            'peserta' => ['#087a75', '#065f5b', '#e6f7f4', '#c9e8e2'],
        ][$profileRole] ?? ['#087a75', '#065f5b', '#e6f7f4', '#c9e8e2'];
    @endphp
    <style>
        .profile-page{--profile-accent:{{ $profileTone[0] }};--profile-deep:{{ $profileTone[1] }};--profile-soft:{{ $profileTone[2] }};--profile-line:{{ $profileTone[3] }};max-width:1120px;font-family:'Plus Jakarta Sans',sans-serif}
        .profile-hero{display:flex;align-items:center;gap:18px;padding:22px 24px;border:1px solid var(--profile-line);border-radius:24px;background:linear-gradient(115deg,var(--profile-deep),var(--profile-accent));color:#fff;box-shadow:0 16px 34px color-mix(in srgb,var(--profile-accent) 20%,transparent)}
        .profile-hero .profile-avatar{width:62px;height:62px;flex:none;border:3px solid #ffffff55;border-radius:20px;overflow:hidden;background:#fff}.profile-hero .profile-avatar img{width:100%;height:100%;object-fit:cover}.profile-hero p{margin:0;color:#ffffffb8;font-size:12px;font-weight:700}.profile-hero h2{margin:3px 0 4px;color:#fff;font-size:22px;font-weight:900;letter-spacing:-.035em}.profile-role{display:inline-flex;margin-bottom:6px;border:1px solid #ffffff38;border-radius:999px;padding:5px 9px;background:#ffffff16;color:#fff;font-size:10px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}.profile-hero-action{margin-left:auto;border:1px solid #ffffff55;border-radius:12px;padding:10px 13px;background:#fff;color:var(--profile-deep);font-size:12px;font-weight:900;text-decoration:none}
        .profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.profile-card{border:1px solid var(--profile-line)!important;border-radius:22px!important;background:#fff;box-shadow:0 10px 26px color-mix(in srgb,var(--profile-accent) 8%,transparent)!important}.profile-card h2{letter-spacing:-.025em}.profile-page input{border-color:var(--profile-line)!important;background:#fff!important;color:#173d3a!important}.profile-page input:focus{border-color:var(--profile-accent)!important;box-shadow:0 0 0 4px color-mix(in srgb,var(--profile-accent) 16%,transparent)!important}.profile-page label{color:color-mix(in srgb,var(--profile-deep) 76%,#334155)!important}.profile-primary{background:linear-gradient(115deg,var(--profile-accent),var(--profile-deep))!important;box-shadow:0 9px 18px color-mix(in srgb,var(--profile-accent) 22%,transparent)}.profile-dark{background:var(--profile-deep)!important}.profile-photo-card{border-top:4px solid var(--profile-accent)!important}.profile-photo-layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:24px;align-items:start;width:100%;max-width:100%}.profile-photo-layout>*{min-width:0}.avatar-picker{display:flex;gap:10px;overflow-x:auto;padding:4px 2px 12px;scrollbar-width:thin}.avatar-picker label{flex:0 0 auto}.profile-page .avatar-choice{display:block!important;width:64px!important;height:64px!important;min-width:64px!important;aspect-ratio:1!important;border:3px solid transparent;border-radius:18px!important;background-color:var(--profile-soft)!important;background-image:url('{{ asset('images/avatars/spmb-characters-v1.png') }}')!important;background-position:var(--avatar-x) var(--avatar-y)!important;background-size:500% auto!important;background-repeat:no-repeat!important;transition:.18s ease}.profile-page .avatar-choice:hover{transform:translateY(-2px);border-color:var(--profile-line)!important}.profile-page .peer:checked+.avatar-choice{border-color:var(--profile-accent)!important;box-shadow:0 0 0 3px var(--profile-soft)!important}.avatar-current-preview{display:block;width:76px;height:76px;margin:0 auto;border:3px solid var(--profile-accent);border-radius:22px;background-color:var(--profile-soft);background-image:url('{{ asset('images/avatars/spmb-characters-v1.png') }}');background-position:var(--avatar-x) var(--avatar-y);background-size:500% auto;background-repeat:no-repeat;box-shadow:0 0 0 4px #fff}.profile-upload{border:1.5px dashed var(--profile-line);border-radius:16px;padding:14px;background:var(--profile-soft)}.profile-upload input{background:#fff!important}.profile-cropper{border:1px solid var(--profile-line);border-radius:18px;padding:14px;background:linear-gradient(145deg,#fff,var(--profile-soft))}.profile-modal-backdrop{position:fixed;inset:0;z-index:70;display:flex;align-items:center;justify-content:center;padding:16px;background:#0f172a8c;backdrop-filter:blur(4px)}.profile-modal{width:min(760px,100%);max-width:100%;max-height:calc(100vh - 32px);overflow-y:auto;overflow-x:hidden;border:1px solid var(--profile-line);border-radius:24px;background:#fff;box-shadow:0 28px 72px #0f172a55}.profile-modal-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 20px;border-bottom:1px solid var(--profile-line)}.profile-modal-close{display:grid;place-items:center;width:36px;height:36px;border-radius:12px;background:var(--profile-soft);color:var(--profile-deep);font-size:23px;line-height:1}.profile-modal-body{min-width:0;padding:20px;overflow-x:hidden}
        @media(max-width:760px){.profile-hero{display:grid;grid-template-columns:52px minmax(0,1fr);align-items:start;gap:12px;padding:18px;border-radius:20px}.profile-hero .profile-avatar{width:52px;height:52px;border-radius:17px}.profile-hero h2{font-size:18px}.profile-hero-action{grid-column:1/-1;width:100%;margin:2px 0 0;text-align:center;align-self:auto}.profile-grid,.profile-photo-layout{grid-template-columns:minmax(0,1fr);gap:12px}.profile-card{border-radius:18px!important}.profile-page{gap:12px!important}.profile-page .avatar-choice{width:56px!important;height:56px!important;min-width:56px!important;border-radius:16px!important}.avatar-picker{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px;overflow:visible;padding-bottom:4px}.avatar-picker label{display:flex;justify-content:center}}
    </style>
    <div class="profile-page mx-auto max-w-6xl space-y-4" x-data="profileAvatarCropper()">
        <section class="profile-hero">
            <div class="profile-avatar"><x-user-avatar :user="$user" size="h-full w-full" /></div>
            <div class="min-w-0"><span class="profile-role">{{ $profileRoleLabel }}</span><h2 class="truncate">{{ $user->name }}</h2><p>{{ $user->phone ?: 'Nomor WhatsApp belum diisi' }}</p></div>
            <button type="button" @click="photoOpen=true" class="profile-hero-action">Kelola foto</button>
        </section>
        <div class="profile-grid">
        <section class="profile-card rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 font-black text-emerald-700">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</div>
                <div><h2 class="text-base font-black text-slate-950">Informasi akun</h2><p class="text-xs text-slate-500">Data yang digunakan saat masuk.</p></div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" class="mt-4 space-y-3">
                @csrf
                @method('PATCH')
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-black text-slate-700">Nama lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" class="w-full rounded-xl border-2 border-slate-200 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <label for="phone" class="mb-1.5 block text-sm font-black text-slate-700">Nomor WhatsApp</label>
                    <input id="phone" name="phone" type="tel" inputmode="numeric" value="{{ old('phone', $user->phone) }}" required autocomplete="tel" class="w-full rounded-xl border-2 border-slate-200 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>
                <button class="profile-primary w-full rounded-xl px-5 py-2.5 text-sm font-black text-white">Simpan Profil</button>
                @if(session('status') === 'profile-updated')<p class="rounded-xl bg-emerald-50 p-3 text-sm font-bold text-emerald-700">Profil berhasil diperbarui.</p>@endif
            </form>
            @if($phoneVerification)
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-black text-amber-900">Verifikasi nomor WhatsApp baru</p>
                    <p class="mt-1 text-xs font-semibold text-amber-800">Kode dikirim ke {{ $phoneVerification->phone }}. Nomor lama tetap aktif sampai kode dikonfirmasi.</p>
                    <form method="POST" action="{{ route('profile.phone.verify') }}" class="mt-3 flex gap-2">@csrf<input name="code" inputmode="numeric" maxlength="6" required class="min-w-0 flex-1 rounded-xl border border-amber-300 bg-white px-3 py-2 text-sm font-black tracking-[.2em] text-slate-800" placeholder="000000"><button class="rounded-xl bg-amber-600 px-4 py-2 text-xs font-black text-white">Verifikasi</button></form>
                    <form method="POST" action="{{ route('profile.phone.resend') }}" class="mt-2">@csrf<button class="text-xs font-black text-amber-800 underline underline-offset-4">Kirim ulang kode</button></form>
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>
            @endif
            @if(session('status') === 'phone-verification-sent')<p class="mt-3 rounded-xl bg-amber-50 p-3 text-sm font-bold text-amber-800">Kode verifikasi telah dikirim ke nomor WhatsApp baru.</p>@endif
            @if(session('status') === 'phone-updated')<p class="mt-3 rounded-xl bg-emerald-50 p-3 text-sm font-bold text-emerald-700">Nomor WhatsApp berhasil diverifikasi dan diperbarui.</p>@endif
        </section>

        <section class="profile-card rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 class="text-base font-black text-slate-950">Ganti kata sandi</h2>
            <p class="mt-1 text-xs text-slate-500">Konfirmasi sandi lama, lalu buat sandi baru.</p>
            <form method="POST" action="{{ route('password.update') }}" class="mt-4 space-y-3" x-data="{ current:false, next:false, confirm:false }">
                @csrf
                @method('PUT')
                <div>
                    <label for="current_password" class="mb-1.5 block text-sm font-black text-slate-700">Kata sandi sekarang</label>
                    <div class="relative"><input id="current_password" name="current_password" :type="current ? 'text' : 'password'" required autocomplete="current-password" class="w-full rounded-xl border-2 border-slate-200 px-4 py-2.5 pr-12 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100"><button type="button" @click="current=!current" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-500" :aria-label="current ? 'Sembunyikan sandi' : 'Lihat sandi'"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
                    <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-black text-slate-700">Kata sandi baru</label>
                    <div class="relative"><input id="password" name="password" :type="next ? 'text' : 'password'" required autocomplete="new-password" class="w-full rounded-xl border-2 border-slate-200 px-4 py-2.5 pr-12 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100"><button type="button" @click="next=!next" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-500" :aria-label="next ? 'Sembunyikan sandi' : 'Lihat sandi'"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
                    <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-black text-slate-700">Ulangi kata sandi baru</label>
                    <div class="relative"><input id="password_confirmation" name="password_confirmation" :type="confirm ? 'text' : 'password'" required autocomplete="new-password" class="w-full rounded-xl border-2 border-slate-200 px-4 py-2.5 pr-12 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-100"><button type="button" @click="confirm=!confirm" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-500" :aria-label="confirm ? 'Sembunyikan sandi' : 'Lihat sandi'"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
                </div>
                <button class="profile-dark w-full rounded-xl px-5 py-2.5 text-sm font-black text-white">Ganti Kata Sandi</button>
                @if(session('status') === 'password-updated')<p class="rounded-xl bg-emerald-50 p-3 text-sm font-bold text-emerald-700">Kata sandi berhasil diperbarui.</p>@endif
            </form>
        </section>
        </div>
        <div x-show="photoOpen" x-cloak @keydown.escape.window="photoOpen=false" class="profile-modal-backdrop" @click.self="photoOpen=false">
            <section class="profile-modal">
                <header class="profile-modal-head"><div class="flex items-center gap-3"><x-user-avatar :user="$user" size="h-10 w-10" class="border-2 border-emerald-100" /><div><h2 class="text-base font-black text-slate-950">Kelola foto profil</h2><p class="text-xs text-slate-500">Pilih avatar atau unggah foto persegi.</p></div></div><button type="button" @click="photoOpen=false" class="profile-modal-close" aria-label="Tutup">&times;</button></header>
                <div class="profile-modal-body"><form method="POST" action="{{ route('profile.avatar.update') }}">
                @csrf @method('PATCH')
                <div class="profile-photo-layout">
                    <div>
                        <div class="flex items-center justify-between gap-3"><p class="text-sm font-black text-slate-700">Pilih avatar</p><span class="rounded-full px-2.5 py-1 text-[11px] font-bold" style="background:var(--profile-soft);color:var(--profile-deep)">10 pilihan avatar</span></div>
                        <div class="avatar-picker mt-3">@for($avatar = 1; $avatar <= 10; $avatar++)@php($column = ($avatar - 1) % 5) @php($row = intdiv($avatar - 1, 5))<label title="Avatar {{ $avatar }}"><input type="radio" name="avatar_choice" value="character_{{ $avatar }}" x-model="avatarChoice" class="peer sr-only"><span class="avatar-choice" style="--avatar-x:{{ $column * 25 }}%;--avatar-y:{{ $row * 100 }}%"></span></label>@endfor</div>
                        <div class="profile-upload mt-4"><label for="profile_photo" class="block text-sm font-black text-slate-700">Unggah foto sendiri</label><p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WEBP, maksimal 5 MB. Foto dapat diatur sebelum disimpan.</p><input id="profile_photo" type="file" accept="image/png,image/jpeg,image/webp" @change="loadFile($event)" class="mt-3 block w-full rounded-xl border p-2 text-xs"></div>
                    </div>
                    <div x-show="hasImage" x-cloak class="profile-cropper"><p class="mb-3 text-sm font-black text-slate-700">Atur potongan foto</p><canvas x-ref="canvas" width="280" height="280" class="w-full rounded-xl bg-slate-100"></canvas><div class="mt-3 space-y-2 text-xs font-bold text-slate-600"><label class="block">Zoom <input type="range" min="1" max="3" step=".05" x-model="zoom" @input="draw()" class="w-full"></label><label class="block">Geser mendatar <input type="range" min="-100" max="100" x-model="offsetX" @input="draw()" class="w-full"></label><label class="block">Geser vertikal <input type="range" min="-100" max="100" x-model="offsetY" @input="draw()" class="w-full"></label></div></div>
                    <div x-show="!hasImage" class="profile-cropper text-center"><span class="avatar-current-preview" :style="avatarPreviewStyle()"></span><p class="mt-3 text-sm font-black text-slate-800">Avatar saat ini</p><p class="mt-1 text-xs text-slate-500">Pratinjau avatar yang dipilih sebelum disimpan.</p></div>
                </div>
                <input type="hidden" name="avatar_crop" x-ref="crop"><button type="submit" @click="prepareCrop()" class="profile-primary mt-5 w-full rounded-xl px-5 py-2.5 text-sm font-black text-white sm:w-auto">Simpan Foto Profil</button>@if(session('status') === 'avatar-updated')<span class="mt-2 block text-sm font-bold text-emerald-700 sm:ml-3 sm:inline">Foto profil diperbarui.</span>@endif
                </form></div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function profileAvatarCropper(){return{photoOpen:false,avatarChoice:'{{ $user->avatar_choice ?? 'character_1' }}',image:null,hasImage:false,zoom:1,offsetX:0,offsetY:0,loadFile(e){const file=e.target.files[0];if(!file)return;if(file.size>5*1024*1024){alert('Ukuran foto maksimal 5 MB.');return}const reader=new FileReader();reader.onload=()=>{this.image=new Image();this.image.onload=()=>{this.hasImage=true;this.avatarChoice=null;this.zoom=1;this.offsetX=0;this.offsetY=0;this.$nextTick(()=>this.draw())};this.image.src=reader.result};reader.readAsDataURL(file)},draw(){if(!this.image||!this.$refs.canvas)return;const c=this.$refs.canvas,ctx=c.getContext('2d'),base=Math.max(c.width/this.image.width,c.height/this.image.height),scale=base*this.zoom,w=this.image.width*scale,h=this.image.height*scale,x=(c.width-w)/2+(this.offsetX/100)*(w-c.width)/2,y=(c.height-h)/2+(this.offsetY/100)*(h-c.height)/2;ctx.clearRect(0,0,c.width,c.height);ctx.drawImage(this.image,x,y,w,h)},avatarPreviewStyle(){const number=parseInt((this.avatarChoice||'character_1').replace('character_',''),10)||1,index=number-1;return `--avatar-x:${(index%5)*25}%;--avatar-y:${Math.floor(index/5)*100}%`},prepareCrop(){this.$refs.crop.value=this.hasImage?this.$refs.canvas.toDataURL('image/jpeg',.9):''}}}
</script>
<style>.avatar-choice{display:block;aspect-ratio:1;border:3px solid transparent;border-radius:9999px;background:#eaf7f5 url('{{ asset('images/avatars/spmb-characters-v1.png') }}') var(--avatar-x) var(--avatar-y)/500% auto no-repeat}.peer:checked+.avatar-choice{border-color:#059669;box-shadow:0 0 0 3px #d1fae5}</style>
@endpush
