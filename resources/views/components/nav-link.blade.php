@props(['href', 'active' => false])

<a href="{{ $href }}"
   {{ $attributes->class([
       'flex items-center gap-4 px-4 py-4 rounded-xl',
       $active ? 'bg-[#F1EFFC] text-[#5D3FD3]' : 'hover:bg-gray-100 text-gray-700',
   ]) }}
   aria-current="{{ $active ? 'page' : false }}">
   {{ $slot }}
</a>