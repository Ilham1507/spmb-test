@props([
    'title' => 'Dashboard',
    'description' => '',
    'accent' => 'emerald',
    'showMenu' => true,
    'badge' => null,
    'navOnly' => false,
    'headingOnly' => false,
])

@php
    $accentClasses = match ($accent) {
        'amber' => 'bg-amber-50 text-amber-700 hover:bg-amber-100',
        'sky' => 'bg-sky-50 text-sky-700 hover:bg-sky-100',
        'teal' => 'bg-teal-50 text-teal-700 hover:bg-teal-100',
        'violet' => 'bg-violet-50 text-violet-700 hover:bg-violet-100',
        default => 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
    };
    $roleName = auth()->user()?->role?->name ?? 'peserta';
    $roleLabel = match ($roleName) {
        'admin' => 'ADMIN SPMB',
        'panitia' => 'PANITIA',
        'bendahara' => 'BENDAHARA',
        'kepala_sekolah' => 'KEPALA SEKOLAH',
        default => 'SPMB ONLINE',
    };
@endphp

@unless($headingOnly)
<nav x-data="{ profileMenu:false }" class="portal-topbar portal-topbar-{{ $accent }} relative z-50 flex h-[76px] shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 shadow-sm lg:px-7">
    @if($showMenu)
        <button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition md:hidden {{ $accentClasses }}" @click="mobileMenuOpen=true" aria-label="Buka menu"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg></button>
    @endif
    <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 text-slate-950 no-underline">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-emerald-100 bg-white shadow-sm"><img src="{{ asset($siteSettings['school_logo']) }}" alt="Logo {{ $siteSettings['school_short_name'] }}" class="h-9 w-9 object-contain"></span>
        <span class="hidden sm:block"><strong class="block text-sm font-black leading-tight">{{ $roleLabel }}</strong><small class="text-[11px] font-bold text-slate-500">{{ $siteSettings['school_short_name'] }}</small></span>
    </a>
    <div class="ml-auto flex items-center gap-2">
        <span data-active-academic-year class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-[10px] font-extrabold text-slate-700 sm:px-3 sm:py-1.5 sm:text-xs">TA {{ $activeAcademicYear?->name ?? '-' }}</span>
        <x-portal-account-menu :accent="$accent" />
    </div>
</nav>
@endunless

@unless($navOnly)
<header class="portal-section-heading portal-page-heading relative z-40 flex min-h-14 shrink-0 items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-2.5 md:px-6">
    <div class="flex min-w-0 items-center gap-3">
        <div class="min-w-0">
            <div class="flex min-w-0 items-center gap-2">
                <h1 class="truncate text-base font-black text-slate-950">{{ $title }}</h1>
                @if($badge)<span class="shrink-0 rounded-full bg-rose-100 px-3 py-1 text-[10px] font-black uppercase text-rose-700">{{ $badge }}</span>@endif
            </div>
        </div>
    </div>
</header>
@endunless
