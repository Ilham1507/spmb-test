@props(['icon' => 'document'])
<svg {{ $attributes->class(['h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($icon)
        @case('phone')<rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M10 5h4M11 18h2"/>@break
        @case('shirt')<path d="m8 4 4 3 4-3 4 3-3 5v8H7v-8L4 7l4-3Z"/>@break
        @case('family')<circle cx="8" cy="8" r="2.5"/><circle cx="16" cy="8" r="2.5"/><path d="M3.5 20a4.5 4.5 0 0 1 9 0M11.5 20a4.5 4.5 0 0 1 9 0"/>@break
        @case('pen')<path d="m16 3 5 5L9 20l-6 1 1-6L16 3ZM13 6l5 5"/>@break
        @case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
        @case('location')<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2"/>@break
        @case('info')<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>@break
        @default<path d="M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h6"/>
    @endswitch
</svg>
