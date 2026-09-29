@extends('layouts.admin')

@section('title', 'Event & Promo')
@section('page_title', 'Event & Promo')
@section('content')
<div x-data="{ open:false, mode:'add', action:'{{ route('admin.event.store') }}', name:'', target:'formulir', applicantId:'', targetItems:[], discountType:'percent', discountValue:'', startsAt:'', endsAt:'', components:@js($feeComponents), selectedTotal(){ return this.components.filter(item => this.targetItems.includes(item.name)).reduce((sum, item) => sum + Number(item.amount || 0), 0) }, discountTotal(){ const total = this.selectedTotal(); return this.discountType === 'percent' ? total * (Number(this.discountValue || 0) / 100) : Math.min(total, Number(this.discountValue || 0)) } }">
    <section class="admin-card bg-white">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
            <div><h2 class="text-lg font-black text-slate-950">Daftar Event</h2></div>
            <button type="button" @click="mode='add'; action='{{ route('admin.event.store') }}'; name=''; target='formulir'; applicantId=''; targetItems=[]; discountType='percent'; discountValue=''; startsAt=''; endsAt=''; open=true" class="btn-primary">+ Tambah Event</button>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($events as $event)
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <div class="min-w-0">
                        @php
                            $eventIsActive = ($event['is_active'] ?? false)
                                && ($event['starts_at'] ?? '') <= now()->toDateString()
                                && ($event['ends_at'] ?? '') >= now()->toDateString();
                            $eventItems = collect($event['target_items'] ?? [])->filter();
                            if ($eventItems->isEmpty() && filled($event['target_item'] ?? null)) {
                                $eventItems->push($event['target_item']);
                            }
                        @endphp
                        <div class="flex flex-wrap items-center gap-2"><h3 class="font-black text-slate-800">{{ $event['name'] ?? '-' }}</h3><span class="rounded-full px-2 py-1 text-[10px] font-black {{ $eventIsActive ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500' }}">{{ $eventIsActive ? 'Berlaku' : 'Belum / sudah berakhir' }}</span></div><p class="mt-1 text-xs text-slate-500">{{ ($event['target'] ?? '') === 'formulir' ? 'Formulir' : (($event['target'] ?? '') === 'daftar_ulang' ? 'Daftar Ulang' : 'Semua Tagihan') }} · {{ ($event['discount_type'] ?? '') === 'percent' ? rtrim(rtrim(number_format((float) ($event['discount_value'] ?? 0), 2, ',', '.'), '0'), ',').'%' : 'Rp '.number_format((float) ($event['discount_value'] ?? 0), 0, ',', '.') }} · {{ $event['starts_at'] ?? '-' }} – {{ $event['ends_at'] ?? '-' }}</p>@if($event['applicant_label'] ?? null)<p class="mt-2 text-xs font-bold text-blue-700">Khusus peserta: {{ $event['applicant_label'] }}@if($eventItems->isNotEmpty()) · Komponen: {{ $eventItems->implode(', ') }}@endif</p>@endif
                    </div>
                    <div class="flex shrink-0 gap-2"><button type="button" @click="mode='edit'; action='{{ route('admin.event.update', $event['id']) }}'; name=@js($event['name'] ?? ''); target=@js($event['target'] ?? 'formulir'); applicantId=@js((string) ($event['applicant_id'] ?? '')); targetItems=@js(collect($event['target_items'] ?? [])->filter()->when(empty($event['target_items'] ?? []) && filled($event['target_item'] ?? null), fn ($items) => $items->push($event['target_item']))->values()); discountType=@js($event['discount_type'] ?? 'percent'); discountValue=@js($event['discount_value'] ?? ''); startsAt=@js($event['starts_at'] ?? ''); endsAt=@js($event['ends_at'] ?? ''); open=true; $nextTick(() => { $refs.targetSelect.value=target; $refs.targetSelect.dispatchEvent(new Event('change',{bubbles:true})); $refs.applicantSelect.value=applicantId; $refs.applicantSelect.dispatchEvent(new Event('change',{bubbles:true})); $refs.discountTypeSelect.value=discountType; $refs.discountTypeSelect.dispatchEvent(new Event('change',{bubbles:true})); })" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-600">Edit</button><form method="POST" action="{{ route('admin.event.destroy', $event['id']) }}" onsubmit="return confirm('Hapus event ini?')">@csrf @method('DELETE')<button type="submit" class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-black text-rose-700">Hapus</button></form></div>
                </div>
            @empty
                <p class="p-5 text-sm text-slate-500">Belum ada event promo.</p>
            @endforelse
        </div>
    </section>

    <template x-teleport="body"><div x-show="open" x-cloak x-transition.opacity @keydown.escape.window="open=false" @click.self="open=false" class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-950/55 p-3 backdrop-blur-[1px] sm:p-4">
        <div class="flex max-h-[calc(100dvh-1.5rem)] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl sm:max-h-[calc(100dvh-2rem)]" @click.stop>
            <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-5 py-4"><h3 class="text-lg font-black text-slate-950" x-text="mode === 'add' ? 'Tambah Event' : 'Edit Event'"></h3><button type="button" @click="open=false" class="text-xl text-slate-400">&times;</button></div>
            <form method="POST" data-select-inline class="flex min-h-0 flex-1 flex-col" x-bind:action="action">
                @csrf <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-5">
                <input name="name" x-model="name" required class="admin-input" placeholder="Nama event">
                <div><label class="admin-label">Jenis tagihan</label><select x-ref="targetSelect" name="target" x-model="target" required class="admin-input"><option value="formulir">Biaya Formulir</option><option value="daftar_ulang">Daftar Ulang</option><option value="semua">Semua Tagihan</option></select></div>
                <div><label class="admin-label">Berlaku untuk peserta</label><select x-ref="applicantSelect" name="applicant_id" x-model="applicantId" class="admin-input"><option value="">Semua peserta</option>@foreach($applicants as $applicant)<option value="{{ $applicant['id'] }}">Khusus: {{ $applicant['label'] }}</option>@endforeach</select></div>
                <div :class="target !== 'daftar_ulang' && 'opacity-55'"><label class="admin-label">Komponen biaya daftar ulang</label><div class="mt-1 max-h-52 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50"><template x-for="item in components" :key="item.name"><label class="flex cursor-pointer items-center justify-between gap-3 px-3 py-2.5 text-sm" :class="target !== 'daftar_ulang' && 'pointer-events-none'"><span class="flex items-center gap-3"><input type="checkbox" name="target_items[]" :value="item.name" x-model="targetItems" :disabled="target !== 'daftar_ulang'" class="h-4 w-4 rounded border-slate-300 text-blue-700"><span class="font-bold text-slate-700" x-text="item.name"></span></span><strong class="text-xs text-slate-500" x-text="'Rp '+Number(item.amount||0).toLocaleString('id-ID')"></strong></label></template></div><div class="mt-2 flex items-center justify-between rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-blue-800"><span x-text="targetItems.length+' komponen dipilih'"></span><span x-text="'Total komponen Rp '+selectedTotal().toLocaleString('id-ID')"></span></div><p class="mt-1 text-xs font-medium text-slate-500">Potongan dihitung otomatis hanya dari komponen yang dicentang.</p></div>
                <div class="grid gap-3 sm:grid-cols-2"><select x-ref="discountTypeSelect" name="discount_type" x-model="discountType" required class="admin-input"><option value="percent">Potongan Persen (%)</option><option value="fixed">Potongan Nominal (Rp)</option></select><input name="discount_value" x-model="discountValue" type="number" min="0" step="0.01" required class="admin-input" placeholder="Nilai potongan"></div>
                <div x-show="target === 'daftar_ulang' && targetItems.length" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-black text-emerald-800"><span>Estimasi total potongan</span><strong class="float-right" x-text="'Rp '+discountTotal().toLocaleString('id-ID')"></strong></div>
                <div class="grid gap-3 sm:grid-cols-2"><input name="starts_at" x-model="startsAt" type="date" required class="admin-input"><input name="ends_at" x-model="endsAt" type="date" required class="admin-input"></div>
                </div><div class="flex shrink-0 justify-end gap-2 border-t border-slate-100 bg-white px-5 py-4"><button type="button" @click="open=false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600">Batal</button><button type="submit" class="btn-primary" x-text="mode === 'add' ? 'Simpan Event' : 'Simpan Perubahan'"></button></div>
            </form>
        </div>
    </div></template>
</div>
@endsection
