@props([
    'user',
    'size' => 'h-8 w-8',
    'withRing' => true,
    'ringClass' => 'ring-2 ring-white',
    'showInitialFallback' => true,
])

@php
    // Extract user data
    $photoData = $user->avatar_url ?? $user->user_profile_photo ?? $user->avatar ?? null;
    $userName = $user->name ?? 'U';
    $userId = $user->id ?? 0;
    
    // Generate avatar initial
    $initial = strtoupper(mb_substr($userName, 0, 1));
    
    // Color options for fallback
    $colors = ['bg-primary/10 text-primary', 'bg-secondary/10 text-secondary', 'bg-accent/20 text-accent'];
    $colorClass = $colors[$userId % count($colors)];
    
    // Merge ring classes if enabled
    $ringClasses = $withRing ? $ringClass : '';
@endphp

@if($photoData)
    <img 
        src="{{ storageUrl($photoData) }}" 
        alt="{{ $userName }}" 
        title="{{ $userName }}"
        class="{{ $size }} rounded-full object-cover flex-shrink-0 {{ $ringClasses }} {{ $attributes->get('class') }}"
        {{ $attributes->except('class') }}
    >
@elseif($showInitialFallback)
    <div class="bg-white rounded-full {{ $size}}">
        <div class="{{ $size }} rounded-full {{ $colorClass }} grid place-items-center font-bold text-sm flex-shrink-0 {{ $ringClasses }} {{ $attributes->get('class') }}" 
            title="{{ $userName }}"
            {{ $attributes->except('class') }}
        >
            {{ $initial }}
        </div>
    </div>
@endif
