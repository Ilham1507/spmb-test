@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => 'Pilih data',
    'required' => false,
    'accent' => 'emerald',
])

@php
    $selected = (string) old($name, $selected ?? '');
    $normalizedOptions = collect($options)->map(function ($option) {
        if (is_array($option)) {
            return [
                'value' => (string) ($option['value'] ?? ''),
                'label' => (string) ($option['label'] ?? $option['value'] ?? ''),
                'description' => $option['description'] ?? null,
            ];
        }

        return [
            'value' => (string) $option,
            'label' => (string) $option,
            'description' => null,
        ];
    })->values();
    $selectedOption = $normalizedOptions->firstWhere('value', $selected);
    $selectedLabel = $selectedOption['label'] ?? $placeholder;
    $accentClasses = [
        'emerald' => 'hover:border-emerald-300 focus:border-emerald-500 focus:ring-emerald-100 hover:bg-emerald-50 hover:text-emerald-700 text-emerald-600',
        'sky' => 'hover:border-sky-300 focus:border-sky-500 focus:ring-sky-100 hover:bg-sky-50 hover:text-sky-700 text-sky-600',
        'amber' => 'hover:border-amber-300 focus:border-amber-500 focus:ring-amber-100 hover:bg-amber-50 hover:text-amber-700 text-amber-600',
    ][$accent] ?? 'hover:border-emerald-300 focus:border-emerald-500 focus:ring-emerald-100 hover:bg-emerald-50 hover:text-emerald-700 text-emerald-600';
@endphp

<div {{ $attributes }}>
    @if($label)
        <label class="text-xs font-black text-slate-700">{{ $label }} @if($required)<span class="text-rose-500">*</span>@endif</label>
    @endif
    <div
        x-data="{ open:false, value:@js($selected), label:@js($selectedLabel) }"
        @click.outside="open=false"
        class="relative mt-2"
    >
        <input type="hidden" name="{{ $name }}" :value="value">
        <button
            type="button"
            @click="open=!open"
            class="flex w-full items-center justify-between rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-black text-slate-800 transition focus:outline-none focus:ring-4 {{ $accentClasses }}"
        >
            <span x-text="label" :class="value ? 'text-slate-900' : 'text-slate-500'"></span>
            <svg class="h-5 w-5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </button>
        <div x-cloak x-show="open" x-transition class="absolute left-0 top-full z-[120] mt-2 max-h-64 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1 shadow-xl">
            @if(!$required)
                <button
                    type="button"
                    @click="value=''; label=@js($placeholder); open=false"
                    class="flex w-full items-center justify-between rounded-xl px-4 py-3 text-left text-sm font-black text-slate-500 hover:bg-slate-50 hover:text-slate-800"
                >
                    {{ $placeholder }}
                    <span x-show="value===''" class="text-xs font-black text-slate-500">Dipilih</span>
                </button>
            @endif
            @forelse($normalizedOptions as $option)
                <button
                    type="button"
                    @click="value=@js($option['value']); label=@js($option['label']); open=false; if (@js($name) === 'tahun_ajaran_id') $dispatch('spmb-academic-year-preview', { label: @js($option['label']) })"
                    class="flex w-full items-center justify-between gap-3 rounded-xl px-4 py-3 text-left text-sm font-black text-slate-700 hover:bg-emerald-50 hover:text-emerald-700"
                >
                    <span>
                        <span class="block">{{ $option['label'] }}</span>
                        @if($option['description'])
                            <span class="mt-0.5 block text-xs font-bold text-slate-400">{{ $option['description'] }}</span>
                        @endif
                    </span>
                    <span x-show="value===@js($option['value'])" class="text-xs font-black {{ str($accentClasses)->contains('text-sky') ? 'text-sky-600' : (str($accentClasses)->contains('text-amber') ? 'text-amber-600' : 'text-emerald-600') }}">Dipilih</span>
                </button>
            @empty
                <div class="px-4 py-3 text-sm font-bold text-slate-500">Data belum tersedia.</div>
            @endforelse
        </div>
    </div>
</div>
