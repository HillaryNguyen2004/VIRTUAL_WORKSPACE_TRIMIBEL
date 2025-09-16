@props(['textColor' => '', 'bgColor' => ''])

<div class="flex gap-2 items-center justify-center w-40 px-3 py-1 rounded-full text-sm {{ $textColor }} {{ $bgColor }}">
    {{ $slot }}
</div>