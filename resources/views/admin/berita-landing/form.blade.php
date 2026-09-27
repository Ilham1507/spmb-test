<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="landing-news-form space-y-4">
    @csrf @if($method !== 'POST') @method($method) @endif
    <label>Judul berita<input name="judul" required value="{{ old('judul', $article?->judul) }}" placeholder="Contoh: Pendaftaran gelombang 1 dibuka"></label>
    <label>Isi berita<textarea name="isi" required rows="6" placeholder="Tulis informasi untuk calon siswa dan orang tua.">{{ old('isi', $article?->isi) }}</textarea></label>
    @php($status = old('status', $article?->status ?? 'publish'))
    <div class="grid gap-4 sm:grid-cols-2">
        <label>Status
            <div class="landing-news-select" x-data="{ open:false, value:@js($status), label:@js($status === 'draft' ? 'Simpan sebagai draf' : 'Terbitkan') }" @click.outside="open=false">
                <input type="hidden" name="status" :value="value">
                <button type="button" class="landing-news-select-button" @click="open=!open" :aria-expanded="open.toString()">
                    <span x-text="label"></span>
                    <svg aria-hidden="true" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="none" stroke="currentColor"><path d="m5 7 5 5 5-5"/></svg>
                </button>
                <div x-cloak x-show="open" x-transition class="landing-news-select-menu">
                    <button type="button" @click="value='draft'; label='Simpan sebagai draf'; open=false" :class="value === 'draft' ? 'is-selected' : ''">Simpan sebagai draf</button>
                    <button type="button" @click="value='publish'; label='Terbitkan'; open=false" :class="value === 'publish' ? 'is-selected' : ''">Terbitkan</button>
                </div>
            </div>
        </label>
        <label>Gambar sampul<input type="file" name="image" accept="image/*"></label><label>Tayang mulai<input type="datetime-local" name="tampil_mulai" value="{{ old('tampil_mulai', $article?->tampil_mulai?->format('Y-m-d\\TH:i')) }}"></label>
    </div>
    <button class="landing-news-save">{{ $submit }}</button>
</form>
