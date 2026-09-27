@props(['user', 'size' => 'h-10 w-10', 'class' => ''])
@php
    $choice = max(1, min(10, (int) str_replace('character_', '', $user?->avatar_choice ?? 'character_1')));
    $column = ($choice - 1) % 5;
    $row = intdiv($choice - 1, 5);
    $photo = $user?->profile_photo_path;
@endphp
@if($photo)
    <img src="{{ asset($photo) }}" alt="Foto profil {{ $user?->name }}" class="{{ $size }} {{ $class }} rounded-full object-cover">
@else
    <span class="avatar-character {{ $size }} {{ $class }}" style="--avatar-x:{{ $column * 25 }}%;--avatar-y:{{ $row * 100 }}%" aria-label="Avatar {{ $user?->name }}"></span>
@once
    <style>.avatar-character{display:inline-block;flex:none;border-radius:9999px;background-color:#eaf7f5;background-image:url('{{ asset('images/avatars/spmb-characters-v1.png') }}');background-position:var(--avatar-x) var(--avatar-y);background-size:500% auto;background-repeat:no-repeat}</style>
@endonce
@endif
