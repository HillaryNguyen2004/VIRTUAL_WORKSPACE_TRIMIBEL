@php
    $currentType = $currentType ?? null;

    $tabs = [
        [
            'key' => 'docs',
            'href' => route('online-docs.docs'),
            'title' => __('online_docs.docs_label'),
            'subtitle' => __('online_docs.docs_page_subtitle'),
        ],
        [
            'key' => 'excel',
            'href' => route('online-docs.excel'),
            'title' => __('online_docs.excel_label'),
            'subtitle' => __('online_docs.excel_page_subtitle'),
        ],
        [
            'key' => 'powerpoint',
            'href' => route('online-docs.powerpoint'),
            'title' => __('online_docs.powerpoint_label'),
            'subtitle' => __('online_docs.powerpoint_page_subtitle'),
        ],
    ];
@endphp

<div class="rounded-xl border border-muted-200 bg-white p-2">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
        @foreach($tabs as $tab)
            <a
                href="{{ $tab['href'] }}"
                class="rounded-lg px-4 py-3 border transition {{ $currentType === $tab['key'] ? 'border-primary/40 bg-primary/5 text-primary' : 'border-transparent hover:border-muted-200 hover:bg-muted-50 text-main' }}"
            >
                <p class="text-sm font-semibold leading-5">{{ $tab['title'] }}</p>
                <p class="text-xs text-muted-500 mt-0.5">{{ $tab['subtitle'] }}</p>
            </a>
        @endforeach
    </div>
</div>