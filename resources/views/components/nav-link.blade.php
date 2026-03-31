@props(['href', 'active' => false])

<a href="{{ $href }}"
   {{ $attributes->class([
       'flex items-center gap-4 px-4 py-3 rounded-xl',
       $active ? 'bg-primary/5 text-primary' : 'hover:bg-muted-50 text-muted-500',
   ]) }}
   aria-current="{{ $active ? 'page' : false }}">
   {{ $slot }}
</a>