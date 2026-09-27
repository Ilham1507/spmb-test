@extends(request()->routeIs('admin.*') ? 'layouts.admin' : (request()->routeIs('bendahara.*') ? 'layouts.bendahara' : (request()->routeIs('kepala-sekolah.*') ? 'layouts.kepala-sekolah' : 'layouts.panitia')))

@section('title', isset($pendaftar) ? 'Edit Formulir Siswa' : 'Bantu Isi Formulir')
@section('page_title', isset($pendaftar) ? 'Edit Formulir Siswa' : 'Bantu Isi Formulir')

@section('content')
@php
    $editing = isset($pendaftar);
    $biodata = $pendaftar?->biodata; $alamat = $pendaftar?->alamat; $sekolah = $pendaftar?->sekolahAsal;
    $ayah = $pendaftar?->dataAyah; $ibu = $pendaftar?->dataIbu; $wali = $pendaftar?->dataWali;
    $routePrefix = request()->routeIs('admin.*') ? 'admin.' : (request()->routeIs('bendahara.*') ? 'bendahara.' : (request()->routeIs('kepala-sekolah.*') ? 'kepala-sekolah.' : 'panitia.'));
    $fieldClass = 'assist-input mt-1.5 w-full rounded-xl px-3.5 py-3 text-sm font-semibold';
    $show = fn (string $key) => in_array($key, $enabledFields, true);
    $labels = collect($fieldGroups)->flatMap(fn ($fields) => $fields);
    $label = fn (string $key, string $fallback) => $labels->get($key, $fallback);
    $required = fn (string $key) => \App\Support\FormFieldCatalog::isRequired($key);
    $hasAddress = collect(['alamat','rt','rw','kelurahan','kecamatan','kabupaten_kota','provinsi','kode_pos','jarak_ke_sekolah'])->contains($show);
    $parents = ['ayah' => ['father', 'Data ayah', $ayah], 'ibu' => ['mother', 'Data ibu', $ibu], 'wali' => ['guardian', 'Data wali (opsional)', $wali]];
    $hasAyah = collect(['nama_ayah','nik_ayah','pendidikan_ayah','pekerjaan_ayah','penghasilan_ayah','no_hp_ayah'])->contains($show);
    $hasIbu = collect(['nama_ibu','nik_ibu','pendidikan_ibu','pekerjaan_ibu','penghasilan_ibu','no_hp_ibu'])->contains($show);
    $hasWali = collect(['nama_wali','nik_wali','pendidikan_wali','pekerjaan_wali','penghasilan_wali','no_hp_wali'])->contains($show);
    $hasSchoolData = collect(['asal_sekolah','npsn','alamat_sekolah','tahun_lulus'])->contains($show);
    $hasJurusan = $show('jurusan') || $jalurs->isNotEmpty();
    $hasContact = true;
    $steps = collect([['label'=>'Biodata'], $hasAddress ? ['label'=>'Alamat'] : null, $hasAyah ? ['label'=>'Ayah'] : null, $hasIbu ? ['label'=>'Ibu'] : null, $hasWali ? ['label'=>'Wali'] : null, $hasSchoolData ? ['label'=>'Sekolah Asal'] : null, $hasJurusan ? ['label'=>'Jurusan'] : null, $hasContact ? ['label'=>'Kontak'] : null])->filter()->values();
    $number = 1; $studentStep = $number++; $addressStep = $hasAddress ? $number++ : null; $ayahStep = $hasAyah ? $number++ : null; $ibuStep = $hasIbu ? $number++ : null; $waliStep = $hasWali ? $number++ : null; $schoolStep = $hasSchoolData ? $number++ : null; $jurusanStep = $hasJurusan ? $number++ : null; $contactStep = $hasContact ? $number++ : null;
    $birthDate = filled($biodata?->birth_date) ? \Illuminate\Support\Carbon::parse($biodata->birth_date)->toDateString() : '';
    $distanceOptions = ['0 - 1000 meter', '1001 - 3000 meter', '3001 - 5000 meter', '5001 - 10000 meter', 'Lebih dari 10000 meter'];
    $requiredInputMap = ['nama_peserta'=>'full_name','nisn'=>'nisn','nik'=>'nik','no_kartu_keluarga'=>'no_kk','jenis_kelamin'=>'gender','tempat_lahir'=>'birth_place','tanggal_lahir'=>'birth_date','agama'=>'religion','alamat'=>'address','rt'=>'rt','rw'=>'rw','kelurahan'=>'village','kecamatan'=>'district','kabupaten_kota'=>'city','provinsi'=>'province','kode_pos'=>'postal_code','jarak_ke_sekolah'=>'distance_range','asal_sekolah'=>'referensi_sekolah_id','npsn'=>'referensi_sekolah_id','alamat_sekolah'=>'referensi_sekolah_id','tahun_lulus'=>'graduation_year','jurusan'=>'major_choice_1','email'=>'email','nama_ayah'=>'father_name','nik_ayah'=>'father_nik','pendidikan_ayah'=>'father_education','pekerjaan_ayah'=>'father_occupation','penghasilan_ayah'=>'father_income','no_hp_ayah'=>'father_phone','nama_ibu'=>'mother_name','nik_ibu'=>'mother_nik','pendidikan_ibu'=>'mother_education','pekerjaan_ibu'=>'mother_occupation','penghasilan_ibu'=>'mother_income','no_hp_ibu'=>'mother_phone','nama_wali'=>'guardian_name','nik_wali'=>'guardian_nik','pendidikan_wali'=>'guardian_education','pekerjaan_wali'=>'guardian_occupation','penghasilan_wali'=>'guardian_income','no_hp_wali'=>'guardian_phone'];
    $requiredInputs = collect($requiredInputMap)->filter(fn ($input, $field) => $required($field))->values()->push('full_name', 'phone')->unique()->values();
@endphp
<style>
.assist-page{--accent:var(--portal-accent,#1559b1);--deep:var(--portal-accent-deep,#0d3476);--soft:var(--portal-soft,#ebf3ff);--line:var(--portal-line,#c9dcfa)}.assist-page [x-cloak]{display:none!important}.assist-hero{overflow:hidden;border-radius:24px;padding:28px;background:linear-gradient(120deg,var(--deep),var(--accent));color:#fff;box-shadow:0 16px 32px #123b7724}.assist-kicker{color:#ffdf70;font-size:11px;font-weight:900;letter-spacing:.16em;text-transform:uppercase}.assist-hero h2{margin:7px 0;color:#fff!important;font-size:26px;font-weight:900}.assist-hero p{max-width:720px;margin:0;color:#e7efff;font-size:13px;font-weight:600;line-height:1.6}.assist-steps{display:flex;flex-wrap:wrap;gap:8px;margin-top:20px}.assist-step{display:flex;align-items:center;gap:7px;border:1px solid #ffffff36;border-radius:999px;padding:8px 11px;font-size:12px;font-weight:800}.assist-step b{display:grid;width:20px;height:20px;place-items:center;border-radius:50%;background:#fff;color:var(--deep);font-size:10px}.assist-card{overflow:hidden;border:1px solid var(--line);border-radius:24px;background:#fff;box-shadow:0 14px 28px #16397414}.assist-section{padding:25px}.assist-head{display:flex;gap:12px;margin-bottom:20px}.assist-index{display:grid;width:34px;height:34px;flex:none;place-items:center;border-radius:11px;background:var(--soft);color:var(--deep);font-size:12px;font-weight:900}.assist-head h3{margin:0;color:#18233a;font-size:18px;font-weight:900}.assist-head p{margin:3px 0 0;color:#64748b;font-size:12px;font-weight:600}.assist-label{display:block;color:#334155;font-size:12px;font-weight:800}.assist-label em{margin-left:3px;color:#e11d48;font-style:normal}.assist-input{border:1.5px solid #d5e2f5!important;background:#fff!important;color:#14213d!important;outline:0}.assist-input:focus{border-color:var(--accent)!important;box-shadow:0 0 0 4px var(--soft)!important}.assist-parent{border:1px solid #dce8f8;border-radius:17px;padding:15px;background:#fbfdff}.assist-parent h4{margin:0 0 12px;color:#1e3a5f;font-size:13px;font-weight:900}.assist-footer{display:flex;align-items:center;gap:10px;border-top:1px solid #e1eaf6;padding:16px 24px;background:#fff}.assist-back{border:1px solid #cfdaeb;border-radius:12px;padding:11px 16px;color:#475569;font-size:13px;font-weight:800}.assist-next{border-radius:12px;padding:11px 17px;background:linear-gradient(135deg,var(--accent),var(--deep));color:#fff;font-size:13px;font-weight:900;box-shadow:0 8px 16px #1559b12a}@media(max-width:640px){.assist-hero{border-radius:20px;padding:21px}.assist-hero h2{font-size:22px}.assist-section{padding:17px}.assist-footer{padding:14px 17px}.assist-footer>*{flex:1;text-align:center}.assist-step{font-size:11px}}
</style>
<script>
function assistedForm(config) {
    return {
        step: 1,
        total: config.total,
        requiredInputs: config.requiredInputs || [],
        stepError: '',
        schoolSearchUrl: config.schoolSearchUrl,
        schoolQuery: config.initialSchoolName || '',
        schoolResults: [],
        schoolLoading: false,
        schoolOpen: false,
        schoolSelectionError: false,
        selectedSchoolId: config.initialSchoolId || '',
        selectedSchool: config.initialSchoolId ? {
            id: config.initialSchoolId, npsn: config.initialNpsn, nama: config.initialSchoolName,
            alamat: config.initialSchoolAddress, alamat_lengkap: config.initialSchoolAddress,
            bentuk_pendidikan: config.initialSchoolForm, status: config.initialSchoolStatus
        } : null,
        async searchSchool() {
            this.selectedSchoolId = '';
            this.selectedSchool = null;
            this.schoolSelectionError = false;
            const query = this.schoolQuery.trim();
            if (query.length < 2) { this.schoolResults = []; this.schoolOpen = false; return; }
            this.schoolLoading = true;
            this.schoolOpen = true;
            try {
                const response = await fetch(`${this.schoolSearchUrl}?q=${encodeURIComponent(query)}`, { headers: { 'Accept': 'application/json' } });
                this.schoolResults = response.ok ? await response.json() : [];
            } catch (_) { this.schoolResults = []; }
            finally { this.schoolLoading = false; }
        },
        selectSchool(school) {
            this.selectedSchool = school;
            this.selectedSchoolId = school.id;
            this.schoolQuery = `${school.nama} - ${school.npsn}`;
            this.schoolSelectionError = false;
            this.schoolResults = [];
            this.schoolOpen = false;
        },
        validateCurrentStep() {
            this.stepError = '';
            const section = this.$el.querySelector(`[data-assist-step="${this.step}"]`);
            const missing = section && [...section.querySelectorAll('[name]')].find((field) => this.requiredInputs.includes(field.name) && !String(field.value || '').trim());
            if (missing) {
                this.stepError = 'Lengkapi semua field wajib sebelum melanjutkan.';
                missing.focus(); missing.reportValidity?.();
                return false;
            }
            if (this.step === config.schoolStep && config.schoolRequired && !this.selectedSchoolId) {
                this.stepError = 'Pilih sekolah SMP atau MTs dari hasil pencarian sebelum melanjutkan.';
                this.schoolSelectionError = true;
                this.$nextTick(() => this.$refs.schoolSearch?.focus());
                return false;
            }
            return true;
        },
        validateSchoolSelection(event) {
            for (let current = 1; current <= this.total; current++) {
                this.step = current;
                if (!this.validateCurrentStep()) { event.preventDefault(); return; }
            }
        }
    };
}
</script>
<div class="assist-page mx-auto max-w-5xl space-y-4" x-data="assistedForm({
        total: {{ $steps->count() }}, requiredInputs: @js($requiredInputs), schoolStep: {{ $schoolStep ?: 1 }}, schoolRequired: {{ $hasSchoolData ? 'true' : 'false' }},
        schoolSearchUrl: @js(route($routePrefix.'pendaftaran_bantuan.sekolah.search')),
        initialSchoolId: @js(old('referensi_sekolah_id', $sekolah?->referensi_sekolah_id ?? '')),
        initialSchoolName: @js(old('school_name', $sekolah?->school_name ?? '')),
        initialNpsn: @js(old('npsn', $sekolah?->npsn ?? '')),
        initialSchoolAddress: @js(old('school_address', $sekolah?->school_address ?? '')),
        initialSchoolForm: @js($sekolah?->bentuk_pendidikan ?? ''),
        initialSchoolStatus: @js($sekolah?->status_sekolah ?? '')
    })">
    <section class="assist-hero">
        <div class="assist-kicker">Pendaftaran dibantu</div>
        <h2>{{ $editing ? 'Perbarui formulir siswa bertahap' : 'Isi formulir siswa bersama panitia' }}</h2>
        <p>Lengkapi satu bagian per tahap. Semua data disimpan dan tautan WhatsApp hanya dikirim setelah tahap terakhir selesai.</p>
        <div class="assist-steps">@foreach($steps as $index => $item)<span class="assist-step" :class="{ 'ring-2 ring-white/80': step === {{ $index + 1 }}, 'opacity-60': step < {{ $index + 1 }} }"><b>{{ $index + 1 }}</b>{{ $item['label'] }}</span>@endforeach</div>
    </section>
    @if(! $editing)<section class="rounded-2xl border px-5 py-4 text-sm font-semibold text-slate-600" style="border-color:var(--line);background:var(--soft)"><b class="text-slate-800">Akun siswa dibuat otomatis.</b><br>Nomor WhatsApp dipakai untuk mengirim tautan aktivasi setelah formulir selesai.</section>@endif
    @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ $editing ? route($routePrefix.'pendaftaran_bantuan.update', $pendaftar) : route($routePrefix.'pendaftaran_bantuan.store') }}" class="assist-card" @submit="validateSchoolSelection($event)">
        @csrf @if($editing) @method('PUT') @endif
        <section class="assist-section" data-assist-step="{{ $studentStep }}" x-show="step === {{ $studentStep }}" x-cloak>
            <header class="assist-head"><span class="assist-index">01</span><div><h3>Akun & biodata siswa</h3><p>Data dasar untuk membuat akun dan identitas pendaftar.</p></div></header>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="assist-label sm:col-span-2">{{ $label('nama_peserta','Nama lengkap') }}<em>*</em><input name="full_name" required value="{{ old('full_name',$biodata?->full_name ?? $pendaftar?->user?->name) }}" class="{{ $fieldClass }}"></label>
                @if($show('nisn'))<label class="assist-label"><span class="flex items-center justify-between gap-2">{{ $label('nisn','NISN') }}@if($required('nisn'))<em>*</em>@endif<a href="https://nisn.data.kemendikdasmen.go.id/index.php/Cindex/formcaribynama" target="_blank" rel="noopener noreferrer" class="text-[11px] font-black" style="color:var(--accent)">Cari NISN ↗</a></span><input name="nisn" maxlength="10" inputmode="numeric" value="{{ old('nisn',$biodata?->nisn) }}" class="{{ $fieldClass }}"><span class="mt-1 block text-[11px] font-semibold text-slate-500">Belum tahu NISN? Gunakan tautan Cari NISN untuk mencarinya berdasarkan nama.</span></label>@endif
                @if($show('nik'))<label class="assist-label">{{ $label('nik','NIK') }}@if($required('nik'))<em>*</em>@endif<input name="nik" maxlength="16" inputmode="numeric" value="{{ old('nik',$biodata?->nik) }}" class="{{ $fieldClass }}"></label>@endif
                @if($show('no_kartu_keluarga'))<label class="assist-label">{{ $label('no_kartu_keluarga','Nomor KK') }}@if($required('no_kartu_keluarga'))<em>*</em>@endif<input name="no_kk" maxlength="16" inputmode="numeric" value="{{ old('no_kk',$biodata?->no_kk) }}" class="{{ $fieldClass }}"></label>@endif
                @if($show('jenis_kelamin'))<label class="assist-label">{{ $label('jenis_kelamin','Jenis kelamin') }}@if($required('jenis_kelamin'))<em>*</em>@endif<select name="gender" class="{{ $fieldClass }}"><option value="">Pilih jenis kelamin</option><option value="L" @selected(old('gender',$biodata?->gender)==='L')>Laki-laki</option><option value="P" @selected(old('gender',$biodata?->gender)==='P')>Perempuan</option></select></label>@endif
                @if($show('tempat_lahir'))<label class="assist-label">{{ $label('tempat_lahir','Tempat lahir') }}@if($required('tempat_lahir'))<em>*</em>@endif<input name="birth_place" value="{{ old('birth_place',$biodata?->birth_place) }}" class="{{ $fieldClass }}"></label>@endif
                @if($show('tanggal_lahir'))<label class="assist-label">{{ $label('tanggal_lahir','Tanggal lahir') }}@if($required('tanggal_lahir'))<em>*</em>@endif<input type="date" name="birth_date" value="{{ old('birth_date',$birthDate) }}" class="{{ $fieldClass }}"></label>@endif
                @if($show('agama'))<label class="assist-label">{{ $label('agama','Agama') }}@if($required('agama'))<em>*</em>@endif<select name="religion" class="{{ $fieldClass }}"><option value="">Pilih agama</option>@foreach($agamas as $agama)<option value="{{ $agama }}" @selected(old('religion',$biodata?->religion)===$agama)>{{ $agama }}</option>@endforeach</select></label>@endif
            </div>
        </section>

        @if($hasAddress)<section class="assist-section" data-assist-step="{{ $addressStep }}" x-show="step === {{ $addressStep }}" x-cloak>
            <header class="assist-head"><span class="assist-index">{{ str_pad($addressStep,2,'0',STR_PAD_LEFT) }}</span><div><h3>Alamat tempat tinggal</h3><p>Isi sesuai domisili siswa saat ini.</p></div></header><div class="grid gap-4 sm:grid-cols-2">
            @if($show('alamat'))<label class="assist-label sm:col-span-2">{{ $label('alamat','Alamat lengkap') }}@if($required('alamat'))<em>*</em>@endif<textarea name="address" rows="3" class="{{ $fieldClass }}">{{ old('address',$alamat?->address) }}</textarea></label>@endif
            @foreach(['rt'=>['rt','RT'],'rw'=>['rw','RW'],'kelurahan'=>['village','Kelurahan / Desa'],'kecamatan'=>['district','Kecamatan'],'kabupaten_kota'=>['city','Kota / Kabupaten'],'provinsi'=>['province','Provinsi'],'kode_pos'=>['postal_code','Kode pos']] as $key => [$input,$fallback]) @if($show($key))<label class="assist-label">{{ $label($key,$fallback) }}@if($required($key))<em>*</em>@endif<input name="{{ $input }}" value="{{ old($input, data_get($alamat,$input)) }}" class="{{ $fieldClass }}"></label>@endif @endforeach
            @if($show('jarak_ke_sekolah'))<label class="assist-label sm:col-span-2">{{ $label('jarak_ke_sekolah','Jarak ke sekolah') }}@if($required('jarak_ke_sekolah'))<em>*</em>@endif<select name="distance_range" class="{{ $fieldClass }}"><option value="">Pilih jarak dari rumah ke sekolah</option>@foreach($distanceOptions as $option)<option value="{{ $option }}" @selected(old('distance_range',$alamat?->distance_range)===$option)>{{ $option }}</option>@endforeach</select></label>@endif
            </div></section>@endif

        @foreach($parents as $group => [$prefix,$title,$person])
            @php
                $visibleParent = ${'has'.ucfirst($group)};
                $parentStep = ${$group.'Step'};
                $keys = ['nama_'.$group,'nik_'.$group,'pendidikan_'.$group,'pekerjaan_'.$group,'penghasilan_'.$group,'no_hp_'.$group];
            @endphp
            @if($visibleParent)<section class="assist-section" data-assist-step="{{ $parentStep }}" x-show="step === {{ $parentStep }}" x-cloak>
                <header class="assist-head"><span class="assist-index">{{ str_pad($parentStep,2,'0',STR_PAD_LEFT) }}</span><div><h3>{{ $title }}</h3><p>Lengkapi data {{ strtolower(str_replace('Data ', '', $title)) }} sesuai field yang diaktifkan.</p></div></header>
                <div class="grid gap-4 sm:grid-cols-2">
                    @if($show('nama_'.$group))<label class="assist-label sm:col-span-2">{{ $label('nama_'.$group,'Nama') }}@if($required('nama_'.$group))<em>*</em>@endif<input name="{{ $prefix }}_name" value="{{ old($prefix.'_name',$person?->name) }}" class="{{ $fieldClass }}"></label>@endif
                    @if($show('nik_'.$group))<label class="assist-label">{{ $label('nik_'.$group,'NIK') }}@if($required('nik_'.$group))<em>*</em>@endif<input name="{{ $prefix }}_nik" value="{{ old($prefix.'_nik',$person?->nik) }}" class="{{ $fieldClass }}"></label>@endif
                    @foreach(['pendidikan'=>['education',$pendidikans,'Pilih pendidikan'],'pekerjaan'=>['occupation',$pekerjaans,'Pilih pekerjaan'],'penghasilan'=>['income',$penghasilans,'Pilih penghasilan']] as $type => [$input,$options,$placeholder]) @if($show($type.'_'.$group))<label class="assist-label">{{ $label($type.'_'.$group,ucfirst($type)) }}@if($required($type.'_'.$group))<em>*</em>@endif<select name="{{ $prefix }}_{{ $input }}" class="{{ $fieldClass }}"><option value="">{{ $placeholder }}</option>@foreach($options as $option)<option value="{{ $option }}" @selected(old($prefix.'_'.$input,$person?->{$input})===$option)>{{ $option }}</option>@endforeach</select></label>@endif @endforeach
                    @if($show('no_hp_'.$group))<label class="assist-label">{{ $label('no_hp_'.$group,'WhatsApp') }}@if($required('no_hp_'.$group))<em>*</em>@endif<input name="{{ $prefix }}_phone" inputmode="numeric" value="{{ old($prefix.'_phone',$person?->phone) }}" class="{{ $fieldClass }}"></label>@endif
                </div>
            </section>@endif
        @endforeach

        @if($hasSchoolData)<section class="assist-section" data-assist-step="{{ $schoolStep }}" x-show="step === {{ $schoolStep }}" x-cloak>
            <header class="assist-head"><span class="assist-index">{{ str_pad($schoolStep,2,'0',STR_PAD_LEFT) }}</span><div><h3>Sekolah asal</h3><p>Cari dari referensi resmi. Hanya SMP dan MTs yang dapat dipilih.</p></div></header>
            <input type="hidden" name="referensi_sekolah_id" :value="selectedSchoolId">
            <div class="rounded-2xl border p-4" style="border-color:var(--line);background:var(--soft)">
                <label class="assist-label" for="assisted-school-search">Cari sekolah SMP / MTs<em>*</em></label>
                <div class="relative mt-1.5">
                    <input id="assisted-school-search" x-ref="schoolSearch" type="search" x-model="schoolQuery" @input.debounce.350ms="searchSchool()" @focus="schoolOpen = schoolQuery.trim().length >= 2" autocomplete="off" class="assist-input w-full rounded-xl px-3.5 py-3 text-sm font-semibold" placeholder="Ketik nama sekolah, NPSN, kecamatan, atau kabupaten">
                    <div x-cloak x-show="schoolOpen" @click.outside="schoolOpen = false" class="absolute inset-x-0 top-[calc(100%+8px)] z-40 max-h-64 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1 shadow-xl">
                        <p x-show="schoolLoading" class="px-3 py-3 text-sm font-semibold text-slate-500">Mencari sekolah…</p>
                        <template x-for="school in schoolResults" :key="school.id">
                            <button type="button" @click="selectSchool(school)" class="block w-full rounded-xl px-3 py-3 text-left hover:bg-slate-50"><span class="flex items-center justify-between gap-2"><span class="font-black text-slate-900" x-text="school.nama"></span><span class="rounded-full px-2 py-0.5 text-[10px] font-black" style="background:var(--soft);color:var(--deep)" x-text="school.bentuk_pendidikan"></span></span><span class="mt-1 block text-xs font-semibold text-slate-500"><span x-text="school.npsn"></span><span x-show="school.alamat_lengkap"> · <span x-text="school.alamat_lengkap"></span></span></span></button>
                        </template>
                        <p x-show="!schoolLoading && schoolResults.length === 0" class="px-3 py-3 text-sm text-slate-500">Tidak ada SMP atau MTs yang cocok.</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-500">Ketik minimal 2 karakter, lalu pilih satu hasil pencarian.</p>
                <p x-cloak x-show="schoolSelectionError" class="mt-2 text-xs font-bold text-rose-600">Pilih sekolah SMP atau MTs dari hasil pencarian.</p>
            </div>
            <div x-cloak x-show="selectedSchool" class="mt-4 grid gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><p class="text-xs font-black uppercase tracking-wide text-emerald-700">Sekolah dipilih</p><p class="mt-1 font-black text-slate-900" x-text="selectedSchool?.nama"></p></div>
                @if($show('npsn'))<div><p class="text-xs font-bold text-slate-500">{{ $label('npsn','NPSN') }}</p><p class="font-bold text-slate-800" x-text="selectedSchool?.npsn"></p></div>@endif
                <div><p class="text-xs font-bold text-slate-500">Jenjang / status</p><p class="font-bold text-slate-800" x-text="[selectedSchool?.bentuk_pendidikan, selectedSchool?.status].filter(Boolean).join(' · ')"></p></div>
                @if($show('alamat_sekolah'))<div class="sm:col-span-2"><p class="text-xs font-bold text-slate-500">{{ $label('alamat_sekolah','Alamat sekolah') }}</p><p class="font-semibold text-slate-700" x-text="selectedSchool?.alamat_lengkap || selectedSchool?.alamat || 'Alamat belum tersedia'"></p></div>@endif
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @if($show('tahun_lulus'))<label class="assist-label">{{ $label('tahun_lulus','Tahun lulus') }}@if($required('tahun_lulus'))<em>*</em>@endif<input type="number" name="graduation_year" value="{{ old('graduation_year',$sekolah?->graduation_year) }}" class="{{ $fieldClass }}"></label>@endif
            </div>
        </section>@endif

        @if($hasJurusan)<section class="assist-section" data-assist-step="{{ $jurusanStep }}" x-show="step === {{ $jurusanStep }}" x-cloak>
            <header class="assist-head"><span class="assist-index">{{ str_pad($jurusanStep,2,'0',STR_PAD_LEFT) }}</span><div><h3>Pilihan jurusan</h3><p>Pilih jurusan dan jalur pendaftaran yang sedang tersedia.</p></div></header><div class="grid gap-4 sm:grid-cols-2">
            @if($show('jurusan'))<label class="assist-label">{{ $label('jurusan','Jurusan pilihan 1') }}@if($required('jurusan'))<em>*</em>@endif<select name="major_choice_1" class="{{ $fieldClass }}"><option value="">Pilih jurusan utama</option>@foreach($jurusans as $jurusan)<option value="{{ $jurusan->id }}" @selected((string)old('major_choice_1',$pendaftar?->major_choice_1)===(string)$jurusan->id)>{{ $jurusan->name }}</option>@endforeach</select></label><label class="assist-label">Jurusan pilihan 2<select name="major_choice_2" class="{{ $fieldClass }}"><option value="">Tidak ada pilihan kedua</option>@foreach($jurusans as $jurusan)<option value="{{ $jurusan->id }}" @selected((string)old('major_choice_2',$pendaftar?->major_choice_2)===(string)$jurusan->id)>{{ $jurusan->name }}</option>@endforeach</select></label>@endif
            @if($jalurs->isNotEmpty())<label class="assist-label sm:col-span-2">Jalur pendaftaran<select name="admission_path_id" class="{{ $fieldClass }}"><option value="">Pilih jalur pendaftaran</option>@foreach($jalurs as $jalur)<option value="{{ $jalur->id }}" @selected((string)old('admission_path_id',$pendaftar?->admission_path_id)===(string)$jalur->id)>{{ $jalur->name }}</option>@endforeach</select></label>@endif
            </div></section>@endif
        <section class="assist-section" data-assist-step="{{ $contactStep }}" x-show="step === {{ $contactStep }}" x-cloak>
            <header class="assist-head"><span class="assist-index">{{ str_pad($contactStep,2,'0',STR_PAD_LEFT) }}</span><div><h3>Data kontak</h3><p>Nomor WhatsApp dipakai untuk mengirim tautan aktivasi setelah formulir disimpan.</p></div></header>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="assist-label">{{ $label('no_handphone','No. HP / WhatsApp') }}<em>*</em><input name="phone" required inputmode="numeric" placeholder="08xxxxxxxxxx" value="{{ old('phone',$pendaftar?->user?->phone ?? $pendaftar?->kontak?->phone) }}" class="{{ $fieldClass }}"></label>
                @if($show('email'))<label class="assist-label">{{ $label('email','Email') }}@if($required('email'))<em>*</em>@endif<input type="email" name="email" value="{{ old('email',$pendaftar?->kontak?->email) }}" class="{{ $fieldClass }}"></label>@endif
            </div>
        </section>
        <footer class="assist-footer"><a x-show="step === 1" href="{{ $editing ? route($routePrefix.'pendaftar.show',$pendaftar) : route($routePrefix.'pendaftar.index') }}" class="assist-back">Batal</a><button x-show="step > 1" type="button" @click="step--" class="assist-back">Kembali</button><span class="mr-auto hidden text-xs font-bold text-slate-500 sm:block" x-text="`Tahap ${step} dari ${total}`"></span><p x-cloak x-show="stepError" class="mr-auto text-xs font-bold text-rose-600" x-text="stepError"></p><button x-show="step < total" type="button" @click="if (validateCurrentStep()) { step++; stepError = '' }" class="assist-next">Lanjut</button><button x-show="step === total" x-cloak type="submit" class="assist-next">{{ $editing ? 'Simpan perubahan' : 'Simpan & kirim aktivasi' }}</button></footer>
    </form>
</div>
@endsection
