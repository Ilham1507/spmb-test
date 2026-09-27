@props(['currentStep' => 1, 'optionalLabel' => null])

@php
    $steps = [
        1 => 'Biodata',
        2 => 'Alamat',
        3 => 'Ayah',
        4 => 'Ibu',
        5 => 'Sekolah Asal',
        6 => 'Jurusan',
        7 => 'Dokumen',
        8 => 'Review'
    ];
@endphp

<div class="mb-8 overflow-x-auto overflow-y-visible pb-2" style="scrollbar-width: none;">
    <div class="flex min-w-max items-center gap-2 pb-2">
        @foreach($steps as $number => $label)
            <div class="flex items-center shrink-0">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border transition-all duration-300 {{ $number < $currentStep ? 'bg-emerald-50 border-emerald-200 text-emerald-700 shadow-sm' : ($number == $currentStep ? 'bg-sky-500 border-sky-600 text-white shadow-md shadow-sky-500/30 transform scale-105' : 'bg-white border-slate-200 text-slate-400') }}">
                    @if($number < $currentStep)
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-xs font-bold whitespace-nowrap">{{ $label }}</span>
                    @elseif($number == $currentStep)
                        <span class="w-4 h-4 flex items-center justify-center rounded-full bg-white text-sky-600 text-[10px] font-bold">{{ $number }}</span>
                        <span class="text-xs font-bold whitespace-nowrap">{{ $label }}</span>
                    @else
                        <span class="w-4 h-4 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 text-[10px] font-bold">{{ $number }}</span>
                        <span class="text-xs font-semibold whitespace-nowrap">{{ $label }}</span>
                    @endif
                </div>
                @if($number < 8)
                    <div class="w-4 h-px {{ $number < $currentStep ? 'bg-emerald-300' : 'bg-slate-200' }} mx-1"></div>
                @endif
            </div>
        @endforeach

        @if($optionalLabel)
            <div class="ml-2 flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-bold text-sky-700">
                <span class="rounded-full bg-white px-2 py-0.5 text-[10px] uppercase tracking-widest text-sky-600">Opsional</span>
                {{ $optionalLabel }}
            </div>
        @endif
    </div>
</div>
