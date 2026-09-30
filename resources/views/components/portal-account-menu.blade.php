@props(['accent' => 'emerald'])
@php
    $tone = match($accent) {
        'amber' => ['hover:border-amber-300 hover:bg-amber-50', 'bg-amber-100 text-amber-700'],
        'sky' => ['hover:border-sky-300 hover:bg-sky-50', 'bg-sky-100 text-sky-700'],
        'teal' => ['hover:border-teal-300 hover:bg-teal-50', 'bg-teal-100 text-teal-700'],
        'violet' => ['hover:border-violet-300 hover:bg-violet-50', 'bg-violet-100 text-violet-700'],
        default => ['hover:border-emerald-300 hover:bg-emerald-50', 'bg-emerald-100 text-emerald-700'],
    };
@endphp
<div x-data="{ profileMenu:false }" data-participant-account-menu class="relative" @participant-tour-open-account.window="profileMenu=true" @participant-tour-close-account.window="profileMenu=false" @click.outside="profileMenu=false">
    <button type="button" data-participant-profile-button @click="profileMenu=!profileMenu" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-left transition {{ $tone[0] }}" :aria-expanded="profileMenu">
        <x-user-avatar :user="auth()->user()" size="h-8 w-8" class="border {{ $tone[1] }}" />
        <span class="hidden max-w-32 md:block"><strong class="block truncate text-xs font-black text-slate-900">{{ auth()->user()?->name }}</strong><small class="block truncate text-[10px] text-slate-500">{{ auth()->user()?->phone }}</small></span>
        <svg class="hidden h-4 w-4 text-slate-500 transition md:block" :class="profileMenu ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
    </button>
    <div x-cloak x-show="profileMenu" x-transition data-participant-account-dropdown class="absolute right-0 z-50 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 shadow-2xl">
        <div class="border-b border-slate-100 px-3 py-2 md:hidden"><p class="truncate text-sm font-black">{{ auth()->user()?->name }}</p><p class="truncate text-xs text-slate-500">{{ auth()->user()?->phone }}</p></div>
        <a data-participant-profile-link href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z M4 21a8 8 0 0 1 16 0"/></svg>Profil Saya</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button data-participant-logout type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm font-bold text-rose-600 hover:bg-rose-50"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12H3m12 0l-4-4m4 4l-4 4M21 4v16"/></svg>Keluar</button></form>
    </div>
</div>
