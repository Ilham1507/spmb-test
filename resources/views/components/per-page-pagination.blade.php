@props(['paginator', 'static' => false, 'forceDown' => false])

@php
    $pageName = $paginator->getPageName();
    $accent = request()->routeIs('bendahara.*') ? '#b45309' : (request()->routeIs('panitia.*') ? '#6d28d9' : (request()->routeIs('admin.*') ? '#15468c' : '#047b73'));
    $query = request()->except(['per_page', $pageName]);
    $selected = \App\Support\Pagination::perPage($paginator->perPage());
@endphp

<div class="spmb-pagination flex flex-col gap-3 border-b border-slate-100 bg-white px-3 py-3 sm:flex-row sm:items-center sm:justify-between" @if($static) data-static-pagination @endif style="--spmb-pagination-accent: {{ $accent }}">
    <form method="GET" class="flex items-center gap-2 text-xs font-bold text-slate-500" @form-select-changed.window="if ($event.detail.name === 'per_page') $nextTick(() => $el.requestSubmit())">
        @foreach($query as $key => $value)
            @if(is_scalar($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <span class="whitespace-nowrap">Tampilkan</span>
        <div class="w-[74px]">
            <x-form-select name="per_page" :options="collect(\App\Support\Pagination::OPTIONS)->mapWithKeys(fn ($option) => [$option => $option])" :value="$selected" placeholder="10" :id="'per-page-'.$pageName" :force-down="$forceDown" />
        </div>
    </form>

    @if($paginator->hasPages())
        <div class="spmb-pagination-links">{{ $paginator->appends($query)->links() }}</div>
    @endif
</div>

@once
<style>
    .spmb-pagination-links nav { justify-content: flex-end; }
    .spmb-pagination-links nav span[aria-current="page"] > span { background: var(--spmb-pagination-accent) !important; border-color: var(--spmb-pagination-accent) !important; color: #fff !important; }
    .spmb-pagination button[id^="per-page-"] { border-color: color-mix(in srgb, var(--spmb-pagination-accent) 26%, #d8e1ed) !important; }
    .spmb-pagination button[id^="per-page-"] > span > span:first-child { background: color-mix(in srgb, var(--spmb-pagination-accent) 10%, white) !important; color: var(--spmb-pagination-accent) !important; }
    .spmb-pagination button[id^="per-page-"]:focus { border-color: var(--spmb-pagination-accent) !important; box-shadow: 0 0 0 4px color-mix(in srgb, var(--spmb-pagination-accent) 14%, transparent) !important; }
    @media (max-width: 640px) { .spmb-pagination-links nav { justify-content: flex-start; } }
    [data-static-pagination] { border: 0; background: transparent; padding: 0; flex: 0 0 auto; }
    [data-static-pagination] .spmb-pagination-links { display: none; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.spmb-pagination').forEach((pagination) => {
            if (pagination.hasAttribute('data-static-pagination')) return;
            if (pagination.closest('.md\\:hidden')) return;
            let container = pagination.parentElement;

            for (let level = 0; container && level < 4; level += 1, container = container.parentElement) {
                const table = container.querySelector('table');
                const tableWrap = table?.closest('.overflow-x-auto') || table?.parentElement;
                if (tableWrap?.parentElement) {
                    tableWrap.parentElement.insertBefore(pagination, tableWrap);
                    break;
                }
            }
        });
    });
</script>
@endonce
