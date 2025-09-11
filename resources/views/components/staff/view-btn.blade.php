@props(['href', 'bgColor', 'content'])

<a href="{{ $href }}" class="w-full text-center text-[#FDFDFF] {{ $bgColor }} py-2 rounded-xl">
    {{ $content }}
</a>