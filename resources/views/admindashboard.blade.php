@extends('layout_dashboard')

@section('content')
    @role('admin')
    <div class="flex flex-col gap-[25px] w-full">
        {{-- First section --}}
        <div class="flex items-center gap-2">
            <h2 class="font-medium text-[28px] md:text-[32px]">{{ __('admin_dashboard.admin_dashboard') }}</h2>
            <div class="bg-blue-100 text-blue-500 text-center text-sm md:text-base w-fit h-fit py-1 px-3 rounded-2xl">
                {{ __('admin_dashboard.admin') }}
            </div>
        </div>

        {{-- Second section --}}
        <div class="flex flex-col md:flex-row items-center gap-3 w-full h-fit">
            <x-admin.summary :title="__('admin_dashboard.pending_tasks')" :number="5"
                animationDelay="[animation-delay:150ms]" colorNumber="text-[#5D3FD3]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-7 h-7 fill-[#5D3FD3]">
                    <path
                        d="M197.8 100.3C208.7 107.9 211.3 122.9 203.7 133.7L147.7 213.7C143.6 219.5 137.2 223.2 130.1 223.8C123 224.4 116 222 111 217L71 177C61.7 167.6 61.7 152.4 71 143C80.3 133.6 95.6 133.7 105 143L124.8 162.8L164.4 106.2C172 95.3 187 92.7 197.8 100.3zM197.8 260.3C208.7 267.9 211.3 282.9 203.7 293.7L147.7 373.7C143.6 379.5 137.2 383.2 130.1 383.8C123 384.4 116 382 111 377L71 337C61.6 327.6 61.6 312.4 71 303.1C80.4 293.8 95.6 293.7 104.9 303.1L124.7 322.9L164.3 266.3C171.9 255.4 186.9 252.8 197.7 260.4zM288 160C288 142.3 302.3 128 320 128L544 128C561.7 128 576 142.3 576 160C576 177.7 561.7 192 544 192L320 192C302.3 192 288 177.7 288 160zM288 320C288 302.3 302.3 288 320 288L544 288C561.7 288 576 302.3 576 320C576 337.7 561.7 352 544 352L320 352C302.3 352 288 337.7 288 320zM224 480C224 462.3 238.3 448 256 448L544 448C561.7 448 576 462.3 576 480C576 497.7 561.7 512 544 512L256 512C238.3 512 224 497.7 224 480zM128 440C150.1 440 168 457.9 168 480C168 502.1 150.1 520 128 520C105.9 520 88 502.1 88 480C88 457.9 105.9 440 128 440z" />
                </svg>
            </x-admin.summary>
            <x-admin.summary :title="__('admin_dashboard.active_projects')" :number="10"
                animationDelay="[animation-delay:200ms]" colorNumber="text-green-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-7 h-7 fill-green-400">
                    <path
                        d="M64 144C64 117.5 85.5 96 112 96L208 96C234.5 96 256 117.5 256 144L256 160L384 160L384 144C384 117.5 405.5 96 432 96L528 96C554.5 96 576 117.5 576 144L576 240C576 266.5 554.5 288 528 288L432 288C405.5 288 384 266.5 384 240L384 224L256 224L256 240C256 247.3 254.3 254.3 251.4 260.5L320 352L400 352C426.5 352 448 373.5 448 400L448 496C448 522.5 426.5 544 400 544L304 544C277.5 544 256 522.5 256 496L256 400C256 392.7 257.7 385.7 260.6 379.5L192 288L112 288C85.5 288 64 266.5 64 240L64 144z" />
                </svg>
            </x-admin.summary>
            <x-admin.summary :title="__('admin_dashboard.total_users')" :number="10"
                animationDelay="[animation-delay:250ms]" colorNumber="text-blue-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-7 h-7 fill-blue-400">
                    <path
                        d="M320 80C377.4 80 424 126.6 424 184C424 241.4 377.4 288 320 288C262.6 288 216 241.4 216 184C216 126.6 262.6 80 320 80zM96 152C135.8 152 168 184.2 168 224C168 263.8 135.8 296 96 296C56.2 296 24 263.8 24 224C24 184.2 56.2 152 96 152zM0 480C0 409.3 57.3 352 128 352C140.8 352 153.2 353.9 164.9 357.4C132 394.2 112 442.8 112 496L112 512C112 523.4 114.4 534.2 118.7 544L32 544C14.3 544 0 529.7 0 512L0 480zM521.3 544C525.6 534.2 528 523.4 528 512L528 496C528 442.8 508 394.2 475.1 357.4C486.8 353.9 499.2 352 512 352C582.7 352 640 409.3 640 480L640 512C640 529.7 625.7 544 608 544L521.3 544zM472 224C472 184.2 504.2 152 544 152C583.8 152 616 184.2 616 224C616 263.8 583.8 296 544 296C504.2 296 472 263.8 472 224zM160 496C160 407.6 231.6 336 320 336C408.4 336 480 407.6 480 496L480 512C480 529.7 465.7 544 448 544L192 544C174.3 544 160 529.7 160 512L160 496z" />
                </svg>
            </x-admin.summary>
        </div>

        {{-- Third section --}}
        <div class="grid grid-cols-1 md:grid-cols-2 w-full gap-3">
            <x-admin.manage-action :title="__('admin_dashboard.user_management')"
                :subtitle="__('admin_dashboard.user_management_description')" :btnLabel="__('admin_dashboard.add_new_user')"
                :hrefAll="route('users.index')" :hrefAction="route('admin.users.create')"
                animationDelay="[animation-delay:300ms]" bgBtn="bg-indigo-600" bgHoverBtn="opacity-95">
                <ul class="text-sm list-disc pl-4">
                    <li>{{ __('admin_dashboard.user_management_item_1') }}</li>
                    <li>{{ __('admin_dashboard.user_management_item_2') }}</li>
                    <li>{{ __('admin_dashboard.user_management_item_3') }}</li>
                </ul>
            </x-admin.manage-action>
            <x-admin.manage-action :title="__('admin_dashboard.permission_management')"
                :subtitle="__('admin_dashboard.permission_management_description')"
                :btnLabel="__('admin_dashboard.edit_permissions')" :hrefAll="route('admin.permissions')"
                :hrefAction="route('admin.permissions')" animationDelay="[animation-delay:300ms]" bgBtn="bg-violet-600"
                bgHoverBtn="opacity-95">
                <ul class="text-sm list-disc pl-4">
                    <li>{{ __('admin_dashboard.permission_management_item_1') }}</li>
                </ul>
            </x-admin.manage-action>
            <x-admin.manage-action :title="__('admin_dashboard.task_management')"
                :subtitle="__('admin_dashboard.task_management_description')" :btnLabel="__('admin_dashboard.edit_tasks')"
                :hrefAll="route('tasks.index')" :hrefAction="route('tasks.index')" animationDelay="[animation-delay:350ms]"
                bgBtn="bg-amber-600" bgHoverBtn="opacity-95">
                <ul class="text-sm list-disc pl-4">
                    <li>{{ __('admin_dashboard.task_management_item_1') }}</li>
                    <li>{{ __('admin_dashboard.task_management_item_2') }}</li>
                    <li>{{ __('admin_dashboard.task_management_item_3') }}</li>
                </ul>
            </x-admin.manage-action>
            <x-admin.manage-action :title="__('admin_dashboard.company_hours_management')"
                :subtitle="__('admin_dashboard.company_hours_management_description')"
                :btnLabel="__('admin_dashboard.create_new_company_hours')" :hrefAll="route('companyhour.index')"
                :hrefAction="route('companyhour.create')" animationDelay="[animation-delay:350ms]" bgBtn="bg-teal-600"
                bgHoverBtn="opacity-95">
                <ul class="text-sm list-disc pl-4">
                    <li>{{ __('admin_dashboard.company_hours_crud') }}</li>
                    <li>{{ __('admin_dashboard.set_company_hours') }}</li>
                    <li>{{ __('admin_dashboard.manage_hours_policies') }}</li>
                </ul>
            </x-admin.manage-action>
            <x-admin.manage-action :title="__('admin_dashboard.campaign_management')"
                :subtitle="__('admin_dashboard.campaign_management_description')"
                :btnLabel="__('admin_dashboard.create_new_campaign')" :hrefAll="route('campaigns.index')"
                :hrefAction="route('campaigns.create')" animationDelay="[animation-delay:400ms]" bgBtn="bg-rose-600"
                bgHoverBtn="opacity-95">
                <ul class="text-sm list-disc pl-4">
                    <li>{{ __('admin_dashboard.campaign_crud') }}</li>
                    <li>{{ __('admin_dashboard.assign_users_to_campaign') }}</li>
                    <li>{{ __('admin_dashboard.schedule_and_send') }}</li>
                </ul>
            </x-admin.manage-action>
            <x-admin.manage-action :title="__('admin_dashboard.email_template_management')"
                :subtitle="__('admin_dashboard.email_template_management_description')"
                :btnLabel="__('admin_dashboard.create_new_template')" :hrefAll="route('email-templates.index')"
                :hrefAction="route('email-templates.create')" animationDelay="[animation-delay:400ms]" bgBtn="bg-sky-600"
                bgHoverBtn="opacity-95">
                <ul class="text-sm list-disc pl-4">
                    <li>{{ __('admin_dashboard.email_template_crud') }}</li>
                    <li>{{ __('admin_dashboard.support_shortcodes') }}</li>
                    <li>{{ __('admin_dashboard.assign_to_campaign') }}</li>
                </ul>
            </x-admin.manage-action>
            <x-admin.manage-action
                :title="__('admin_dashboard.project_management')"
                :subtitle="__('admin_dashboard.project_management_description')"
                :btnLabel="__('admin_dashboard.view_projects')"
                :hrefAll="route('projects.index')"
                :hrefAction="route('projects.create')"
                animationDelay="[animation-delay:375ms]"
                bgBtn="bg-emerald-600"
                bgHoverBtn="opacity-95"
            >
                <ul class="text-sm list-disc pl-4">
                    <li>{{ __('admin_dashboard.project_assign_staff') }}</li>
                    <li>{{ __('admin_dashboard.project_track_progress') }}</li>
                    <li>{{ __('admin_dashboard.project_manage_status') }}</li>
                </ul>
            </x-admin.manage-action>
            <div
                class="flex flex-col gap-3 w-full h-full bg-[#FDFDFF] shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] rounded-[20px] py-5 px-6 animate-fade-in-up [animation-delay:450ms]">
                <div class="flex items-center justify-between w-full gap-2">
                    <p class="text-[20px] font-medium">{{ __('admin_dashboard.recent_check_ins') }}</p>
                    <a href="{{ route('users.checkin_index') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path
                                d="M408 64L552 64C565.3 64 576 74.7 576 88L576 232C576 241.7 570.2 250.5 561.2 254.2C552.2 257.9 541.9 255.9 535 249L496 210L409 297C399.6 306.4 384.4 306.4 375.1 297L343.1 265C333.7 255.6 333.7 240.4 343.1 231.1L430.1 144.1L391.1 105.1C384.2 98.2 382.2 87.9 385.9 78.9C389.6 69.9 398.3 64 408 64zM232 576L88 576C74.7 576 64 565.3 64 552L64 408C64 398.3 69.8 389.5 78.8 385.8C87.8 382.1 98.1 384.2 105 391L144 430L231 343C240.4 333.6 255.6 333.6 264.9 343L296.9 375C306.3 384.4 306.3 399.6 296.9 408.9L209.9 495.9L248.9 534.9C255.8 541.8 257.8 552.1 254.1 561.1C250.4 570.1 241.7 576 232 576z" />
                        </svg>
                    </a>
                </div>
                <div class="w-full overflow-x-auto">
                    <table class="w-full">
                        <thead class="text-gray-400 text-sm uppercase border-b">
                            <tr>
                                <td class="py-3 pl-4 pr-3 text-left font-medium">{{ __('admin_dashboard.user') }}</td>
                                <td class="py-3 pl-4 pr-3 text-left font-medium">{{ __('admin_dashboard.date') }}</td>
                                <td class="py-3 pl-4 pr-3 text-left font-medium">{{ __('admin_dashboard.check_in') }}</td>
                                <td class="py-3 pl-4 pr-3 text-left font-medium">{{ __('admin_dashboard.check_out') }}</td>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($recentCheckIns->take(3) as $log)
                                <tr>
                                    <td class="py-3 pl-4 pr-3">{{ $log->user_name }}</td>
                                    <td class="py-3 pl-4 pr-3">{{ $log->date }}</td>
                                    <td class="py-3 pl-4 pr-3">{{ $log->check_in_time ?? '-' }}</td>
                                    <td class="py-3 pl-4 pr-3">{{ $log->check_out_time ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">
                                        {{ __('admin_dashboard.no_check_ins') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                class="flex flex-col gap-3 w-full h-full bg-[#FDFDFF] shadow-[0_4px_40px_0_rgba(32,27,53,0.1)] rounded-[20px] py-5 px-6 animate-fade-in-up [animation-delay:450ms]">
                <div class="flex items-center justify-between w-full gap-2">
                    <p class="text-[20px] font-medium">{{ __('admin_dashboard.recent_task_submissions') }}</p>
                    <a href="{{ route('admin.activity.logs') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-5 h-5 fill-[#5D3FD3]">
                            <path
                                d="M408 64L552 64C565.3 64 576 74.7 576 88L576 232C576 241.7 570.2 250.5 561.2 254.2C552.2 257.9 541.9 255.9 535 249L496 210L409 297C399.6 306.4 384.4 306.4 375.1 297L343.1 265C333.7 255.6 333.7 240.4 343.1 231.1L430.1 144.1L391.1 105.1C384.2 98.2 382.2 87.9 385.9 78.9C389.6 69.9 398.3 64 408 64zM232 576L88 576C74.7 576 64 565.3 64 552L64 408C64 398.3 69.8 389.5 78.8 385.8C87.8 382.1 98.1 384.2 105 391L144 430L231 343C240.4 333.6 255.6 333.6 264.9 343L296.9 375C306.3 384.4 306.3 399.6 296.9 408.9L209.9 495.9L248.9 534.9C255.8 541.8 257.8 552.1 254.1 561.1C250.4 570.1 241.7 576 232 576z" />
                        </svg>
                    </a>
                </div>
                <div class="w-full overflow-x-auto">
                    <table class="w-full">
                        <thead class="text-gray-400 text-sm uppercase border-b">
                            <tr>
                                <td class="py-3 pl-4 pr-3 text-left font-medium">{{ __('admin_dashboard.user') }}</td>
                                <td class="py-3 pl-4 pr-3 text-left font-medium">{{ __('admin_dashboard.task') }}</td>
                                <td class="py-3 pl-4 pr-3 text-left font-medium">{{ __('admin_dashboard.deadline') }}</td>
                                <td class="py-3 pl-4 pr-3 text-left font-medium">{{ __('admin_dashboard.status') }}</td>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($recentLogs->take(3) as $log)
                                <tr>
                                    <td class="py-3 pl-4 pr-3">{{ $log->user->name ?? 'N/A' }}</td>
                                    <td class="py-3 pl-4 pr-3">{{ $log->action }}</td>
                                    <td class="py-3 pl-4 pr-3">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="py-3 pl-4 pr-3">{{ \Illuminate\Support\Str::limit($log->description, 30) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">
                                        {{ __('admin_dashboard.no_activity_logs') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- <div class="container py-4">
                                                                        <h1 class="mb-2 fw-bold">{{ __('admin_dashboard.admin_dashboard') }}</h1>
                                                                        <p class="mb-4">{{ __('admin_dashboard.welcome_message') }}</p>

                                                                        <div class="row mb-4">
                                                                            <div class="col-md-4 mb-3">
                                                                                <div class="card text-center shadow-sm border-0">
                                                                                    <div class="card-body">
                                                                                        <div class="mb-2" style="font-size:2rem; color:#377dff;"><i class="bi bi-list-task"></i></div>
                                                                                        <div class="fw-bold text-secondary">{{ __('admin_dashboard.pending_tasks') }}</div>
                                                                                        <div class="h4 mb-0">5</div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-4 mb-3">
                                                                                <div class="card text-center shadow-sm border-0">
                                                                                    <div class="card-body">
                                                                                        <div class="mb-2" style="font-size:2rem; color:#00b96b;"><i class="bi bi-kanban"></i></div>
                                                                                        <div class="fw-bold text-secondary">{{ __('admin_dashboard.active_projects') }}</div>
                                                                                        <div class="h4 mb-0">3/4</div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-4 mb-3">
                                                                                <div class="card text-center shadow-sm border-0">
                                                                                    <div class="card-body">
                                                                                        <div class="mb-2" style="font-size:2rem; color:#a259f7;"><i class="bi bi-people"></i></div>
                                                                                        <div class="fw-bold text-secondary">{{ __('admin_dashboard.total_users') }}</div>
                                                                                        <div class="h4 mb-0">12</div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="row mb-4">
                                                                            <div class="col-md-6 mb-3">
                                                                                <div class="card shadow-sm border-0 h-100">
                                                                                    <div class="card-body">
                                                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                            <strong>{{ __('admin_dashboard.task_management') }}</strong>
                                                                                            <a href="{{ route('tasks.index') }}" class="small text-primary">{{ __('admin_dashboard.view_all') }} <i class="bi bi-list"></i></a>
                                                                                        </div>
                                                                                        <div class="text-secondary mb-2">{{ __('admin_dashboard.task_management_description') }}</div>
                                                                                        <ul class="mb-3 ps-3">
                                                                                            <li class="mb-1">{{ __('admin_dashboard.task_management_item_1') }}</li>
                                                                                            <li class="mb-1">{{ __('admin_dashboard.task_management_item_2') }}</li>
                                                                                            <li class="mb-1">{{ __('admin_dashboard.task_management_item_3') }}</li>
                                                                                        </ul>
                                                                                        <a href="{{ route('tasks.index') }}" class="btn btn-primary w-100" style="background:#2563eb;border:none;">
                                                                                            <i class="bi bi-check2-circle"></i> {{ __('admin_dashboard.edit_tasks') }}
                                                                                        </a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <div class="col-md-6 mb-3">
                                                                                <div class="card shadow-sm border-0 h-100">
                                                                                    <div class="card-body">
                                                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                            <strong>{{ __('admin_dashboard.permission_management') }}</strong>
                                                                                            <a href="{{ route('admin.permissions') }}" class="small text-primary">{{ __('admin_dashboard.view_all') }} <i class="bi bi-list"></i></a>
                                                                                        </div>
                                                                                        <div class="text-secondary mb-2">{{ __('admin_dashboard.permission_management_description') }}</div>
                                                                                        <ul class="mb-3 ps-3">
                                                                                            <li class="mb-1">{{ __('admin_dashboard.permission_management_item_1') }}</li>
                                                                                        </ul>
                                                                                        <a href="{{ route('admin.permissions') }}" class="btn w-100" style="background:#00b96b;color:#fff;border:none;">
                                                                                            <i class="bi bi-folder-plus"></i> {{ __('admin_dashboard.edit_permissions') }}
                                                                                        </a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="row mb-4">
                                                                            <div class="col-md-6 mb-3">
                                                                                <div class="card shadow-sm border-0 h-100">
                                                                                    <div class="card-body">
                                                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                            <strong>{{ __('admin_dashboard.user_management') }}</strong>
                                                                                            <a href="{{ route('users.index') }}" class="small text-primary">{{ __('admin_dashboard.view_all') }} <i class="bi bi-list"></i></a>
                                                                                        </div>
                                                                                        <div class="text-secondary mb-2">{{ __('admin_dashboard.user_management_description') }}</div>
                                                                                        <ul class="mb-3 ps-3">
                                                                                            <li class="mb-1">{{ __('admin_dashboard.user_management_item_1') }}</li>
                                                                                            <li class="mb-1">{{ __('admin_dashboard.user_management_item_2') }}</li>
                                                                                            <li class="mb-1">{{ __('admin_dashboard.user_management_item_3') }}</li>
                                                                                        </ul>
                                                                                        <a href="{{ route('admin.users.create') }}" class="btn w-100" style="background:#a259f7;color:#fff;border:none;">
                                                                                            <i class="bi bi-person-plus"></i> {{ __('admin_dashboard.add_new_user') }}
                                                                                        </a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <div class="col-md-6 mb-3">
                                                                                <div class="card shadow-sm border-0 h-100">
                                                                                    <div class="card-body">
                                                                                        <strong>{{ __('admin_dashboard.recent_task_submissions') }}</strong>
                                                                                        <table class="table table-sm mt-3">
                                                                                            <thead>
                                                                                                <tr>
                                                                                                    <th>{{ __('admin_dashboard.id') }}</th>
                                                                                                    <th>{{ __('admin_dashboard.user') }}</th>
                                                                                                    <th>{{ __('admin_dashboard.task') }}</th>
                                                                                                    <th>{{ __('admin_dashboard.deadline') }}</th>
                                                                                                    <th>{{ __('admin_dashboard.status') }}</th>
                                                                                                </tr>
                                                                                            </thead>
                                                                                            <tbody>
                                                                                                @forelse($recentLogs as $log)
                                                                                                    <tr>
                                                                                                        <td>{{ $log->id }}</td>
                                                                                                        <td>{{ $log->user->name ?? 'N/A' }}</td>
                                                                                                        <td>{{ $log->action }}</td>
                                                                                                        <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                                                                                        <td>
                                                                                                            <span class="badge rounded-pill bg-info text-dark">
                                                                                                                {{ \Illuminate\Support\Str::limit($log->description, 30) }}
                                                                                                            </span>
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                @empty
                                                                                                    <tr>
                                                                                                        <td colspan="5" class="text-center text-muted">
                                                                                                            {{ __('admin_dashboard.no_activity_logs') }}
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                @endforelse
                                                                                            </tbody>
                                                                                        </table>
                                                                                        <a href="{{ route('admin.activity.logs') }}" class="small text-primary">{{ __('admin_dashboard.view_all_tasks') }} <i class="bi bi-list"></i></a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="row mb-4">
                                                                            <div class="col-md-6 mb-3">
                                                                                <div class="card shadow-sm border-0 h-100">
                                                                                    <div class="card-body">
                                                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                            <strong>{{ __('admin_dashboard.campaign_management') }}</strong>
                                                                                            <a href="{{ route('campaigns.index') }}" class="small text-primary">
                                                                                                {{ __('admin_dashboard.view_all') }} <i class="bi bi-list"></i>
                                                                                            </a>
                                                                                        </div>
                                                                                        <div class="text-secondary mb-2">{{ __('admin_dashboard.campaign_management_description') }}</div>
                                                                                        <ul class="mb-3 ps-3">
                                                                                            <li class="mb-1">{{ __('admin_dashboard.campaign_crud') }}</li>
                                                                                            <li class="mb-1">{{ __('admin_dashboard.assign_users_to_campaign') }}</li>
                                                                                            <li class="mb-1">{{ __('admin_dashboard.schedule_and_send') }}</li>
                                                                                        </ul>
                                                                                        <a href="{{ route('campaigns.create') }}" class="btn w-100" style="background:#ff6b6b;color:#fff;border:none;">
                                                                                            <i class="bi bi-envelope-plus"></i> {{ __('admin_dashboard.create_new_campaign') }}
                                                                                        </a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <div class="col-md-6 mb-3">
                                                                                <div class="card shadow-sm border-0 h-100">
                                                                                    <div class="card-body">
                                                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                            <strong>{{ __('admin_dashboard.email_template_management') }}</strong>
                                                                                            <a href="{{ route('email-templates.index') }}" class="small text-primary">
                                                                                                {{ __('admin_dashboard.view_all') }} <i class="bi bi-list"></i>
                                                                                            </a>
                                                                                        </div>
                                                                                        <div class="text-secondary mb-2">{{ __('admin_dashboard.email_template_management_description') }}</div>
                                                                                        <ul class="mb-3 ps-3">
                                                                                            <li class="mb-1">{{ __('admin_dashboard.email_template_crud') }}</li>
                                                                                            <li class="mb-1">{{ __('admin_dashboard.support_shortcodes') }}</li>
                                                                                            <li class="mb-1">{{ __('admin_dashboard.assign_to_campaign') }}</li>
                                                                                        </ul>
                                                                                        <a href="{{ route('email-templates.create') }}" class="btn w-100" style="background:#4b6cb7;color:#fff;border:none;">
                                                                                            <i class="bi bi-file-earmark-plus"></i> {{ __('admin_dashboard.create_new_template') }}
                                                                                        </a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="row mb-4">
                                                                            <div class="col-md-6 mb-3">
                                                                                <div class="card shadow-sm border-0 h-100">
                                                                                    <div class="card-body">
                                                                                        <strong>{{ __('admin_dashboard.recent_check_ins') }}</strong>
                                                                                        <table class="table table-sm mt-3">
                                                                                            <thead>
                                                                                                <tr>
                                                                                                    <th>{{ __('admin_dashboard.user') }}</th>
                                                                                                    <th>{{ __('admin_dashboard.date') }}</th>
                                                                                                    <th>{{ __('admin_dashboard.check_in') }}</th>
                                                                                                    <th>{{ __('admin_dashboard.check_out') }}</th>
                                                                                                </tr>
                                                                                            </thead>
                                                                                            <tbody>
                                                                                                @forelse($recentCheckIns as $log)
                                                                                                    <tr>
                                                                                                        <td>{{ $log->user_name }}</td>
                                                                                                        <td>{{ $log->date }}</td>
                                                                                                        <td>{{ $log->check_in_time ?? '-' }}</td>
                                                                                                        <td>{{ $log->check_out_time ?? '-' }}</td>
                                                                                                    </tr>
                                                                                                @empty
                                                                                                    <tr>
                                                                                                        <td colspan="4" class="text-center text-muted">
                                                                                                            {{ __('admin_dashboard.no_check_ins') }}
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                @endforelse
                                                                                            </tbody>
                                                                                        </table>
                                                                                        <a href="{{ route('users.checkin_index') }}" class="small text-primary">
                                                                                            {{ __('admin_dashboard.view_all_checkins') }} <i class="bi bi-list"></i>
                                                                                        </a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <div class="col-md-6 mb-3">
                                                                                <div class="card shadow-sm border-0 h-100">
                                                                                    <div class="card-body">
                                                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                            <strong>{{ __('admin_dashboard.company_hours_management') }}</strong>
                                                                                            <a href="{{ route('companyhour.index') }}" class="small text-primary">
                                                                                                {{ __('admin_dashboard.view_all') }} <i class="bi bi-list"></i>
                                                                                            </a>
                                                                                        </div>
                                                                                        <div class="text-secondary mb-2">{{ __('admin_dashboard.company_hours_management_description') }}</div>
                                                                                        <ul class="mb-3 ps-3">
                                                                                            <li class="mb-1">{{ __('admin_dashboard.company_hours_crud') }}</li>
                                                                                            <li class="mb-1">{{ __('admin_dashboard.set_company_hours') }}</li>
                                                                                            <li class="mb-1">{{ __('admin_dashboard.manage_hours_policies') }}</li>
                                                                                        </ul>
                                                                                        <a href="{{ route('companyhour.create') }}" class="btn w-100" style="background:#f39c12;color:#fff;border:none;">
                                                                                            <i class="bi bi-clock"></i> {{ __('admin_dashboard.create_new_company_hours') }}
                                                                                        </a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="row mb-4">
                                                                            <div class="col-12">
                                                                                <div class="card p-3 shadow-sm border-0">
                                                                                    <strong>{{ __('admin_dashboard.quick_actions') }}</strong>
                                                                                    <div class="row mt-3">
                                                                                        <div class="col-md-3 mb-2">
                                                                                            <a href="{{ route('tasks.index') }}" class="btn btn-outline-primary w-100"><i class="bi bi-check2-square"></i> {{ __('admin_dashboard.review_tasks') }}</a>
                                                                                        </div>
                                                                                        <div class="col-md-3 mb-2">
                                                                                            <a href="{{ route('tasks.create') }}" class="btn btn-outline-success w-100"><i class="bi bi-folder-plus"></i> {{ __('admin_dashboard.new_task') }}</a>
                                                                                        </div>
                                                                                        <div class="col-md-3 mb-2">
                                                                                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-people"></i> {{ __('admin_dashboard.manage_users') }}</a>
                                                                                        </div>
                                                                                        <div class="col-md-3 mb-2">
                                                                                            <a href="#" class="btn btn-outline-info w-100"><i class="bi bi-bar-chart"></i> {{ __('admin_dashboard.view_reports') }}</a>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="row mb-4">
                                                                            <div class="col-12">
                                                                                <div class="card p-3 shadow-sm border-0">
                                                                                    <div class="row text-center">
                                                                                        <div class="col-md-4 mb-2">
                                                                                            <div class="fw-bold">{{ __('admin_dashboard.todays_tasks') }}</div>
                                                                                            <div class="h4">7</div>
                                                                                            <a href="#" class="small text-primary">{{ __('admin_dashboard.view_details') }} <i class="bi bi-list"></i></a>
                                                                                        </div>
                                                                                        <div class="col-md-4 mb-2">
                                                                                            <div class="fw-bold">{{ __('admin_dashboard.project_completion') }}</div>
                                                                                            <div class="h4">75%</div>
                                                                                            <a href="#" class="small text-primary">{{ __('admin_dashboard.view_progress') }} <i class="bi bi-bar-chart"></i></a>
                                                                                        </div>
                                                                                        <div class="col-md-4 mb-2">
                                                                                            <div class="fw-bold">{{ __('admin_dashboard.unassigned_tasks') }}</div>
                                                                                            <div class="h4">3</div>
                                                                                            <a href="#" class="small text-primary">{{ __('admin_dashboard.assign_now') }} <i class="bi bi-person-lines-fill"></i></a>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="text-end text-muted mt-2">
                                                                                        {{ __('admin_dashboard.current_date') }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    @else
                                                                    <div class="container py-4">
                                                                        <h3 class="text-danger">{{ __('admin_dashboard.access_denied') }}</h3>
                                                                        <p>{{ __('admin_dashboard.no_permission') }}</p>
                                                                    </div>
                                                                    @endrole -->

@endsection