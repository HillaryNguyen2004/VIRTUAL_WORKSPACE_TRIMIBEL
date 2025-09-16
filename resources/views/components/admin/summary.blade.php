@props(['title' => '', 'number' => '', 'animationDelay' => '', 'colorNumber' => ''])

<div class="flex flex-col gap-3 w-full h-full bg-[#FDFDFF] shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] rounded-[20px] py-5 px-6 animate-fade-in-up {{ $animationDelay }}">
    {{ $slot }}
    <div class="flex w-full items-center justify-between gap-2">
        <p class="md:text-lg font-medium">{{ $title }}</p>
        <p class="text-lg md:text-xl font-medium {{ $colorNumber }}">{{ $number }}</p>
    </div>
</div>