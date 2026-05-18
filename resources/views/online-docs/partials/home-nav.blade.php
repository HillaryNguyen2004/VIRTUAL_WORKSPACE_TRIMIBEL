@php
    $currentType = $currentType ?? null;

    $tabs = [
        [
            'key'      => 'docs',
            'href'     => route('online-docs.docs'),
            'title'    => __('online_docs.docs_label'),
            'subtitle' => __('online_docs.docs_page_subtitle'),
            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        ],
        [
            'key'      => 'excel',
            'href'     => route('online-docs.excel'),
            'title'    => __('online_docs.excel_label'),
            'subtitle' => __('online_docs.excel_page_subtitle'),
            'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M10 3v18M14 3v18M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>',
        ],
        [
            'key'      => 'powerpoint',
            'href'     => route('online-docs.powerpoint'),
            'title'    => __('online_docs.powerpoint_label'),
            'subtitle' => __('online_docs.powerpoint_page_subtitle'),
            'icon'     => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2" stroke-linecap="round" stroke-linejoin="round"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M12 17v4"/>',
        ],
    ];
@endphp

<div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 p-2">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-1.5">
        @foreach($tabs as $tab)
            @php $active = $currentType === $tab['key']; @endphp
            <a
                href="{{ $tab['href'] }}"
                class="group flex items-center gap-3 rounded-xl px-4 py-3 transition-all {{ $active ? 'bg-primary/5 border border-primary/20 text-primary' : 'border border-transparent hover:border-muted-200 hover:bg-muted-50 text-main' }}"
            >
                <span class="shrink-0 inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $active ? 'bg-primary/10 text-primary' : 'bg-muted-100 text-muted-500 group-hover:bg-primary/5 group-hover:text-primary' }} transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        {!! $tab['icon'] !!}
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold leading-5 truncate">{{ $tab['title'] }}</p>
                    <p class="text-xs {{ $active ? 'text-primary/70' : 'text-muted-400' }} mt-0.5 truncate">{{ $tab['subtitle'] }}</p>
                </div>
                @if($active)
                    <span class="ml-auto shrink-0 h-1.5 w-1.5 rounded-full bg-primary"></span>
                @endif
            </a>
        @endforeach
    </div>
</div>
