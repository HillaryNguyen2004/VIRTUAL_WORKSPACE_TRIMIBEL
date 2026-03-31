@props(['emailTemplates', 'class' => ''])

<div class="flex flex-col h-full {{ $class }}">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-main">{{ __('admin_dashboard.email_templates') }}</h3>
        <a href="{{ route('email-templates.index') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-primary/5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
        </a>
    </div>

    {{-- Template List --}}
    <div class="flex flex-col gap-4 flex-1">
        @foreach($emailTemplates->take(4) as $template)
            <x-white-card-container color="primary/50" class="items-center gap-4 group">
                {{-- Circular ID Ring --}}
                <div class="relative w-12 h-12 flex-none">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                        <path class="text-primary/10"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="3" />
                        <path class="text-primary/40 group-hover:text-primary transition-colors duration-300"
                                stroke-dasharray="{{ rand(40, 85) }}, 100"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="3"
                                stroke-linecap="round" />
                    </svg>
                    {{-- ID Number --}}
                    <div class="absolute inset-0 flex items-center justify-center text-sm font-bold text-main">
                        {{ $template->id }}
                    </div>
                </div>

                {{-- Text Content --}}
                <div class="flex-1 min-w-0">
                    <h4 class="text-base font-bold text-main group-hover:text-primary transition-colors">{{ $template->name }}</h4>
                    <p class="text-xs text-muted-500 mt-1 truncate">{{ $template->subject }}</p>
                </div>
            </x-white-card-container>
        @endforeach
    </div>
</div>