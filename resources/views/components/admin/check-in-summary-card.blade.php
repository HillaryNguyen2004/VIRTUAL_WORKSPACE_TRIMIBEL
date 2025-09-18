@props(['textColor' => '', 'borderColor' => '', 'number' => '', 'title' => ''])

<div class="flex flex-col items-center justify-center gap-2 w-full h-28 rounded-2xl border {{ $borderColor }}">
    <p class="font-medium text-base md:text-lg {{ $textColor }}">{{ $number }}</p>
    <p class="text-sm md:text-base">{{ $title }}</p>
</div>