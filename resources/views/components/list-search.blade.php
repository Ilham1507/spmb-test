@props([
    'name' => 'search',
    'value' => null,
    'placeholder' => 'Cari data',
])

@php($accent = request()->routeIs('bendahara.*') ? '#b45309' : (request()->routeIs('admin.*') ? '#15468c' : '#047b73'))
<label class="spmb-list-search relative block" style="--spmb-search-accent: {{ $accent }}; --spmb-search-ring: color-mix(in srgb, {{ $accent }} 14%, transparent)">
    <svg class="spmb-list-search-icon pointer-events-none h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path stroke-linecap="round" d="m16 16 4 4"/></svg>
    <input name="{{ $name }}" value="{{ $value ?? request($name) }}" placeholder="{{ $placeholder }}" style="padding-left: 48px !important; background-image: none !important;" {{ $attributes->merge(['class' => 'w-full rounded-xl border border-slate-200 bg-white py-2.5 pr-3 text-sm font-semibold text-slate-700 outline-none transition placeholder:font-medium placeholder:text-slate-400 focus:border-[var(--spmb-search-accent,#047b73)] focus:ring-4 focus:ring-[color:var(--spmb-search-ring,rgba(4,123,115,.12))]']) }}>
</label>

@once
<style>
    .spmb-list-search { min-height: 42px; }
    .spmb-list-search input { padding-left: 48px !important; background-image: none !important; }
    .spmb-list-search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); z-index: 1; }
</style>
@endonce
