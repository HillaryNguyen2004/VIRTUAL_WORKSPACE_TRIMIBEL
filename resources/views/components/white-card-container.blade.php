@props(['color' => '', 'class' => ''])

<div class="flex bg-white border border-muted-300 hover:border-{{ $color }} transition-all duration-300 rounded-2xl  {{ $class }}">
    {{ $slot }}
</div>