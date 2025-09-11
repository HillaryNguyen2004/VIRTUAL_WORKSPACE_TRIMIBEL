@props(['animationDelay'])

<div
    class="flex flex-col justify-between w-full h-[270px] xl:h-60 bg-[#FDFDFF] shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] rounded-[20px] py-5 px-6 animate-fade-in-up {{ $animationDelay }}">
    {{ $slot }}
</div>