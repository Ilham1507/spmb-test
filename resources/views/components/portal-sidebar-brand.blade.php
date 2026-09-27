@props([
    'title' => null,
    'subtitle' => null,
    'storageKey' => 'spmb-sidebar-mini',
    'accent' => 'emerald',
])

<div class="portal-sidebar-brand portal-sidebar-brand-{{ $accent }} flex items-center justify-between gap-3 border-b border-slate-200 p-4 md:hidden">
    <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3" :class="sidebarMini ? 'md:justify-center' : ''">
        <span class="portal-sidebar-logo"><img src="{{ asset($siteSettings['school_logo']) }}" alt="Logo {{ $siteSettings['school_short_name'] }}"></span>
        <span class="min-w-0" :class="sidebarMini ? 'md:hidden' : ''">
            <strong class="block truncate">{{ $title ?: $siteSettings['portal_name'] }}</strong>
            <small>{{ $subtitle ?: 'Portal SPMB' }}</small>
        </span>
    </a>
    <button type="button" class="portal-sidebar-close md:hidden" @click="mobileMenuOpen = false" aria-label="Tutup menu">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>
