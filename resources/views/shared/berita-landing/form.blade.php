@php
    $status = old('status', $article?->status ?? 'publish');
    $inlineMedia = collect($article?->content_blocks ?? [])->filter(fn ($block) => ($block['type'] ?? null) === 'image' && filled($block['path'] ?? null))->values();
    if ($inlineMedia->isEmpty() && $article) {
        $inlineMedia = collect($article->gallery ?? [])->slice(1)->values()->map(fn ($path) => ['type' => 'image', 'path' => $path, 'caption' => '']);
    }
@endphp
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="article-editor-form">
    @csrf @if($method !== 'POST') @method($method) @endif

    <section class="article-editor-section article-editor-section-main">
        <div class="article-editor-section-head"><span>01</span><div><h2>Isi artikel</h2><p>Tulis judul dan cerita utama. Gunakan satu baris kosong untuk memisahkan paragraf.</p></div></div>
        <label class="article-editor-label">Judul berita<input name="judul" required value="{{ old('judul', $article?->judul) }}" placeholder="Contoh: Sholat Istisqa Bersama SMK Muhammadiyah Cileungsi"></label>
        <label class="article-editor-label article-content-input">Isi berita<textarea name="isi" required rows="13" placeholder="Tulis artikel di sini. Pisahkan setiap bagian cerita dengan satu baris kosong.">{{ old('isi', $article?->isi) }}</textarea></label>
    </section>

    <section class="article-editor-section">
        <div class="article-editor-section-head"><span>02</span><div><h2>Foto artikel</h2><p>Unggah sampul dan dokumentasi kegiatan. Sistem menyusun foto sesuai deskripsi yang Anda tulis.</p></div></div>
        <div class="article-upload-actions">
            <label class="article-upload-button is-cover"><input type="file" name="image" accept="image/*"><span>↑</span><b>Upload gambar sampul</b><small>Foto utama artikel</small></label>
            <label class="article-upload-button"><input id="bulk-inline-upload" type="file" accept="image/*" multiple><span>+</span><b>Upload foto dokumentasi</b><small>Boleh pilih beberapa foto</small></label>
        </div>

        <div class="article-inline-media" id="article-inline-media">
            <div class="article-inline-media-head"><div><b>Foto yang dipilih</b><p>Tambahkan deskripsi singkat agar foto dapat ditempatkan sesuai isi artikel.</p></div></div>
            <div class="article-media-list" id="article-media-list">
                @foreach($inlineMedia as $index => $media)
                    <article class="article-media-row">
                        <div class="article-media-thumb"><img src="{{ asset($media['path']) }}" alt="Dokumentasi artikel"></div>
                        <input type="hidden" name="existing_inline_paths[]" value="{{ $media['path'] }}">
                        <input type="hidden" name="existing_inline_after[]" value="1">
                        <input type="hidden" name="existing_inline_placement[]" value="middle">
                        <label class="article-caption-field">Deskripsi foto<input name="existing_inline_captions[]" value="{{ old('existing_inline_captions.'.$index, $media['caption'] ?? '') }}" placeholder="Contoh: Siswa mengikuti sholat istisqa di lapangan"></label>
                        <label class="article-media-remove"><input type="checkbox" name="remove_inline_images[]" value="{{ $index }}"><span class="article-remove-icon">×</span><span class="article-remove-text"><i>Hapus foto</i><i>Akan dihapus</i></span></label>
                    </article>
                @endforeach
            </div>
            <template id="inline-media-template"><article class="article-media-row is-new"><div class="article-media-thumb article-media-empty">Foto</div><input type="file" required name="inline_images[]" accept="image/*" class="article-new-file"><input type="hidden" name="inline_after[]" value="1"><input type="hidden" name="inline_placement[]" value="middle"><label class="article-caption-field">Deskripsi foto<input name="inline_captions[]" placeholder="Contoh: Siswa mengikuti sholat istisqa di lapangan"></label><button type="button" class="article-remove-new" aria-label="Hapus foto">×</button></article></template>
            <p class="article-media-tip">Deskripsi dipakai sistem untuk mencocokkan foto dengan bagian artikel yang tepat.</p>
        </div>
    </section>

    <section class="article-editor-section">
        <div class="article-editor-section-head"><span>03</span><div><h2>Publikasi</h2><p>Tentukan status dan waktu mulai tayang artikel.</p></div></div>
        <div class="article-publication-grid">
            <label class="article-editor-label">Status<div class="article-status-select" x-data="{ open:false, value:@js($status), label:@js($status === 'draft' ? 'Simpan sebagai draf' : 'Terbitkan') }" @click.outside="open=false"><input type="hidden" name="status" :value="value"><button type="button" @click="open=!open" :aria-expanded="open.toString()"><span x-text="label"></span><svg aria-hidden="true" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="none" stroke="currentColor"><path d="m5 7 5 5 5-5"/></svg></button><div x-cloak x-show="open" x-transition><button type="button" @click="value='draft'; label='Simpan sebagai draf'; open=false">Simpan sebagai draf</button><button type="button" @click="value='publish'; label='Terbitkan'; open=false">Terbitkan</button></div></div></label>
            <label class="article-editor-label">Tayang mulai<input type="datetime-local" name="tampil_mulai" value="{{ old('tampil_mulai', $article?->tampil_mulai?->format('Y-m-d\TH:i')) }}"></label>
        </div>
    </section>

    <div class="article-editor-actions"><a href="{{ route($routePrefix.'.index') }}">Batal</a><button class="article-editor-save">{{ $submit }}</button></div>
</form>
<div class="article-crop-modal" id="article-crop-modal" hidden>
    <div class="article-crop-dialog" role="dialog" aria-modal="true" aria-labelledby="article-crop-title">
        <div class="article-crop-head"><div><b id="article-crop-title">Atur potongan foto</b><span>Geser foto dan atur zoom. Area ini yang akan digunakan.</span></div><button type="button" id="crop-cancel-top" aria-label="Tutup">×</button></div>
        <div class="article-crop-stage" id="article-crop-stage"><canvas id="article-crop-canvas" width="800" height="500"></canvas></div>
        <label class="article-crop-zoom">Zoom<input id="article-crop-zoom" type="range" min="1" max="3" step="0.01" value="1"></label>
        <div class="article-crop-actions"><button type="button" class="article-crop-cancel" id="crop-cancel">Batal</button><button type="button" class="article-crop-confirm" id="crop-confirm">Gunakan foto</button></div>
    </div>
</div><script>
document.addEventListener('DOMContentLoaded', () => {
 const list=document.querySelector('#article-media-list'), template=document.querySelector('#inline-media-template'), uploader=document.querySelector('#bulk-inline-upload'), cover=document.querySelector('input[name="image"]');
 const modal=document.querySelector('#article-crop-modal'), canvas=document.querySelector('#article-crop-canvas'), stage=document.querySelector('#article-crop-stage'), zoom=document.querySelector('#article-crop-zoom'), context=canvas?.getContext('2d');
 let crop=null, resolver=null, dragging=false, dragStart=null;
 const clamp = () => { const width=crop.image.width*crop.scale, height=crop.image.height*crop.scale; crop.x=Math.min(0,Math.max(canvas.width-width,crop.x)); crop.y=Math.min(0,Math.max(canvas.height-height,crop.y)); };
 const draw = () => { if(!crop) return; clamp(); context.clearRect(0,0,canvas.width,canvas.height); context.drawImage(crop.image,crop.x,crop.y,crop.image.width*crop.scale,crop.image.height*crop.scale); };
 const closeCrop = file => { modal.hidden=true; document.body.classList.remove('article-crop-open'); const done=resolver; resolver=null; crop=null; done?.(file); };
 const cropImage = file => new Promise(resolve => {
   const image=new Image(); image.onload=()=>{ const scale=Math.max(canvas.width/image.width,canvas.height/image.height); crop={image,baseScale:scale,scale,x:(canvas.width-image.width*scale)/2,y:(canvas.height-image.height*scale)/2}; zoom.value='1'; resolver=resolve; modal.hidden=false; document.body.classList.add('article-crop-open'); draw(); }; image.src=URL.createObjectURL(file);
 });
 const appendFile = file => {
   if (!file || list.querySelectorAll('.article-media-row').length >= 8) return;
   const fragment=template.content.cloneNode(true), input=fragment.querySelector('.article-new-file'), thumb=fragment.querySelector('.article-media-thumb'), transfer=new DataTransfer(); transfer.items.add(file); input.files=transfer.files;
   const reader=new FileReader(); reader.onload=()=>{thumb.innerHTML=`<img src="${reader.result}" alt="Pratinjau foto">`;thumb.classList.remove('article-media-empty');}; reader.readAsDataURL(file); list.appendChild(fragment);
 };
 cover?.addEventListener('change', async event => { const file=event.target.files?.[0]; if(!file) return; const cropped=await cropImage(file); if(!cropped){ event.target.value=''; return; } const transfer=new DataTransfer(); transfer.items.add(cropped); event.target.files=transfer.files; });
 uploader?.addEventListener('change', async event => { const files=Array.from(event.target.files || []); event.target.value=''; for(const file of files){ const cropped=await cropImage(file); if(cropped) appendFile(cropped); } });
 list?.addEventListener('click', event => { if(event.target.closest('.article-remove-new')) event.target.closest('.article-media-row').remove(); });
 zoom?.addEventListener('input', () => { if(!crop) return; const previous=crop.scale, next=crop.baseScale*Number(zoom.value), centerX=canvas.width/2, centerY=canvas.height/2; crop.x=centerX-(centerX-crop.x)*(next/previous); crop.y=centerY-(centerY-crop.y)*(next/previous); crop.scale=next; draw(); });
 stage?.addEventListener('pointerdown', event => { if(!crop) return; dragging=true; stage.setPointerCapture(event.pointerId); dragStart={x:event.clientX,y:event.clientY,photoX:crop.x,photoY:crop.y}; });
 stage?.addEventListener('pointermove', event => { if(!dragging||!crop) return; const rect=stage.getBoundingClientRect(); crop.x=dragStart.photoX+(event.clientX-dragStart.x)*(canvas.width/rect.width); crop.y=dragStart.photoY+(event.clientY-dragStart.y)*(canvas.height/rect.height); draw(); });
 stage?.addEventListener('pointerup', () => dragging=false);
 document.querySelector('#crop-confirm')?.addEventListener('click', () => canvas.toBlob(blob => { const name=`${Date.now()}-foto-artikel.jpg`; closeCrop(new File([blob],name,{type:'image/jpeg'})); },'image/jpeg',.9));
 document.querySelector('#crop-cancel')?.addEventListener('click', () => closeCrop(null)); document.querySelector('#crop-cancel-top')?.addEventListener('click', () => closeCrop(null));
});
</script>


