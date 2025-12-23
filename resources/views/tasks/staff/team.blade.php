@extends('layout_dashboard')

@section('content')
    @php
        use Illuminate\Support\Facades\Route;

        // Determine dashboard route based on role for the back button
        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        }
    @endphp

    @role('staff')
    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">
        
        {{-- HEADER SECTION --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center w-full mb-8">
            <div class="flex items-center gap-4">
                {{-- Reuse the back button logic/component if available, otherwise manual link --}}
                @include('components.back-btn')
                
                <div>
                    <h2 class="font-bold text-3xl text-main tracking-tight">
                        {{ __('staff_team.my_team') }}
                    </h2>
                    <p class="text-muted-500 text-sm mt-2">{{ __('staff_team.subtitle') ?? 'Manage team assignments' }}</p>
                </div>
            </div>
        </div>

        {{-- FLASH MESSAGE (Styled to match theme) --}}
        @if(session('success'))
            <div class="bg-primary/5 border border-primary/20 text-primary px-4 py-3 rounded-xl w-full animate-fade-in-up flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- CARD CONTAINER --}}
        <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 overflow-hidden flex flex-col animate-fade-in-up">

            {{-- SEARCH BAR (Optional - kept simple for this view) --}}
            <div class="p-5 border-b border-muted-200 bg-white">
                <div class="relative w-full md:w-1/3">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-muted-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" placeholder="{{ __('staff_team.search_member') ?? 'Search members...' }}" 
                        class="block w-full pl-10 bg-canvas border border-muted-200 text-main py-2.5 px-4 rounded-xl placeholder-muted-400 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                </div>
            </div>

            {{-- TABLE SECTION --}}
            <div class="overflow-x-auto w-full">
                <table class="w-full table-fixed">
                    <thead class="bg-muted-50 border-b border-muted-200">
                        <tr>
                            <th class="w-[30%] py-4 pl-6 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('staff_team.member') }}</th>
                            <th class="w-[25%] py-4 px-3 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('staff_team.email') }}</th>
                            <th class="w-[45%] py-4 pr-6 text-left text-xs font-semibold text-muted-400 uppercase tracking-wider">{{ __('staff_team.assign_task') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-muted-100">
                        @forelse ($teamMembers as $member)
                            <tr class="hover:bg-canvas transition-colors">
                                {{-- MEMBER DETAILS --}}
                                <td class="py-4 pl-6">
                                    <div class="flex items-center gap-3">
                                        {{-- Avatar Circle --}}
                                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold ring-1 ring-primary/20 shrink-0">
                                            {{ substr($member->name, 0, 1) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-main text-sm">{{ $member->name }}</span>
                                            <span class="text-xs text-muted-500">@ {{ $member->username }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- CONTACT --}}
                                <td class="py-4 px-3">
                                    <a href="mailto:{{ $member->email }}" class="text-sm text-muted-600 hover:text-primary transition-colors flex items-center gap-2 group">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-muted-400 group-hover:text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        {{ $member->email }}
                                    </a>
                                </td>

                                {{-- TASK ASSIGNMENT FORM --}}
                                <td class="py-4 pr-6">
                                    <form action="{{ route('team.assignTask') }}" method="POST" class="flex gap-4 items-center w-full">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $member->id }}">
                                        
                                        <div class="flex-1">
                                            <select name="task_id"
                                                class="w-full bg-white border border-muted-200 text-main text-sm py-2 px-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all cursor-pointer hover:border-primary/50"
                                                required>
                                                <option value="">{{ __('staff_team.select_task') }}</option>
                                                @foreach($staffTasks as $task)
                                                    <option value="{{ $task->id }}" @selected(old('member_id', $member->id ?? null) == $task->assigned_user_id)>
                                                        {{ $task->title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <button type="submit"
                                            class="shrink-0 bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-xl text-sm font-medium transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                                            <!-- <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg> -->
                                            <span>{{ __('staff_team.assign') }}</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="p-3 rounded-full bg-muted-100 text-muted-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-muted-500 font-medium">{{ __('staff_team.no_team_members') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    @endrole
@endsection