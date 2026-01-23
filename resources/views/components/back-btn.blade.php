@props(['route' => null, 'params' => [], 'class' => ''])

@php
    $href = url()->previous();

    if ($route) {
        $href = route($route, $params);
    }
@endphp

<a href="{{ $href }}"
   class="group flex items-center justify-center w-10 h-10 rounded-xl text-muted-400 hover:text-primary hover:bg-primary/10 transition-colors cursor-pointer {{ $class }}">
    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
    </svg>
</a>