@props(['upcomingCampaigns', 'sentCampaigns', 'class' => ''])

<x-white-card-container color="primary/50" class="p-6 flex-col gap-4 justify-between {{ $class }}">
    <div class="flex items-center justify-between">
        <h3 class="text-md md:text-lg font-semibold text-main">
            {{ __('admin_dashboard.campaign_management') }}</h3>

        <a href="{{ route('campaigns.index') }}" title="{{ __('admin_dashboard.view_all') }}" class="text-muted-400 hover:text-primary transition-colors p-1 rounded-md hover:bg-primary/5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
        </a>
    </div>

    <div class="flex flex-col gap-4">
        <div>
            <h4 class="text-xs font-semibold text-muted-400 uppercase tracking-wider mb-4">
                {{ __('admin_dashboard.campaign_scheduled') }}</h4>
            <ul class="space-y-4">
                @forelse($upcomingCampaigns ?? [] as $camp)
                    <li class="pl-4 border-l-2 border-primary/30 relative">
                        <div class="absolute -left-[5px] w-2 h-2 rounded-full bg-primary"></div>
                        <p class="text-xs font-semibold text-main uppercase tracking-wide">
                            {{ $camp->scheduled_at->format('M d, H:i') }}</p>
                        <p class="text-xs md:text-sm text-muted-600 mt-0.5">{{ $camp->name }}</p>
                        <span
                            class="inline-block mt-2 px-2 py-0.5 bg-primary/10 text-primary text-[10px] rounded-full font-semibold">Scheduled</span>
                    </li>
                @empty
                    <li class="text-xs md:text-sm text-muted-400">
                        {{ __('admin_dashboard.campaign_no_scheduled') }}</li>
                @endforelse
            </ul>
        </div>
        <hr class="border-muted-200">
        <div>
            <h4 class="text-xs font-semibold text-muted-400 uppercase tracking-wider mb-4">
                {{ __('admin_dashboard.campaign_sent') }}</h4>
            <ul class="space-y-4">
                @forelse($sentCampaigns ?? [] as $camp)
                    <li class="flex justify-between items-center group">
                        <div>
                            <p
                                class="text-xs md:text-sm font-semibold text-main group-hover:text-primary transition-colors">
                                {{ $camp->name }}</p>
                            <span
                                class="inline-block mt-1 px-2 py-0.5 bg-accent/10 text-accent text-[10px] rounded-full font-semibold">Sent</span>
                        </div>
                        <div class="text-right">
                            <p class="text-md md:text-lg font-semibold text-main">
                                {{ number_format($camp->sent_count) }}</p>
                            <p class="text-[10px] text-muted-400">
                                {{ __('admin_dashboard.campaign_users_reached') }}</p>
                        </div>
                    </li>
                @empty
                    <li class="text-xs md:text-sm text-muted-400">{{ __('admin_dashboard.campaign_no_sent') }}
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</x-white-card-container>