@props(['route' => '', 'title' => ''])

<div class="flex flex-col gap-6 w-full">
    <a href="{{ route($route) }}" class="text-[#5D3FD3] text-xl font-medium w-fit">
        &larr; {{ __($title) }}
    </a>

    {{ $slot }}
</div>