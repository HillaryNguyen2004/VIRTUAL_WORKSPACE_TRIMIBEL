@extends('layout_dashboard')

@section('content')
    @php
        use Illuminate\Support\Facades\Route;

        // Back button route
        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        }

        // Safety: avoid undefined
        $user = $user ?? null;
        $permissions = $permissions ?? collect();

        // Direct permissions of this substaff user
        $directPermissionNames = $user
            ? $user->getDirectPermissions()->pluck('name')->toArray()
            : [];
    @endphp

    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- HEADER SECTION --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
            @include('components.back-btn' , ['route' => $dashRoute])

            <div class="flex-1">
                <h1 class="font-semibold text-2xl md:text-3xl text-main tracking-tight">
                    {{ __('substaff_permission.title') }}
                </h1>
                <p class="text-muted-500 text-sm md:text-base mt-1">
                    {{ __('substaff_permission.subtitle') }}
                </p>
            </div>

            {{-- Optional: quick jump back to team section on staff dashboard --}}
            <!-- @can('staff.substaff.create')
                @if(Route::has('staff.dashboard'))
                    <a href="{{ route('staff.dashboard') }}#team-members"
                       class="px-3 py-2 text-sm rounded-xl border border-muted-200 bg-white hover:bg-muted-50 transition">
                        Create substaff
                    </a>
                @endif
            @endcan -->
            @if($user)
            <div class="flex items-center bg-primary/10 rounded-2xl py-2 px-6 gap-3 border border-primary/50 transition-all duration-300 group">
                    <div class="relative my-auto">
                        <div class="absolute inset-0 bg-primary/20 rounded-full blur-lg opacity-50"></div>
                        <img src="{{ getUserAvatar($user) }}" alt="leader_avatar" class="relative w-10 h-10 rounded-full">
                    </div>
                    <div class="flex flex-col justify-between h-full">
                        <h3 class="text-primary text-md md:text-lg font-semibold">{{ $user->name }}</h3>
                        <p class="text-primary/70 text-xs md:text-sm">{{ $user->email }}</p>
                    </div>
            </div>
            @endif
        </div>

        {{-- Guard: if no user passed --}}
        @if(!$user)
            <div class="bg-danger/10 text-danger border border-danger/20 text-sm font-medium px-4 py-3 rounded-xl">
                {{ __('substaff_permission.missing_user') }}
            </div>
        @else

            {{-- SUCCESS ALERT --}}
            <!-- @if(session('success'))
                <div class="bg-secondary/10 text-secondary border border-secondary/20 text-sm font-medium px-4 py-3 rounded-xl w-full animate-fade-in-up flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif -->

            {{-- MAIN CARD --}}
            <div class="grid gap-6 animate-fade-in-up [animation-delay:150ms]">
                <form action="{{ route('staff.substaff.permissions.update', $user) }}" method="POST"
                      class="bg-white rounded-2xl border border-muted-300 p-6 hover:border-primary/50 transition-all">
                    @csrf

                    {{-- Card Header --}}
                    <!-- <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 border-b border-muted-100 pb-4">
                        <div>
                            <h5 class="text-xl font-bold text-main flex items-center gap-2">
                                {{ __('user_permission.manage_for') ?? 'Manage permissions for' }}
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary ring-1 ring-inset ring-primary/20">
                                    {{ $user->name }}
                                </span>
                            </h5>
                            <p class="text-sm text-muted-500 mt-1">
                                {{ __('user_permission.note_direct') ?? 'These are DIRECT permissions assigned to this substaff user (model_has_permissions).' }}
                            </p>
                        </div>

                        <div class="text-xs text-muted-500">
                            Current role:
                            <span class="font-semibold text-main">
                                {{ $user->getRoleNames()->implode(', ') ?: 'N/A' }}
                            </span>
                        </div>
                    </div> -->

                    {{-- Permissions Grid --}}
                    @if($permissions->isEmpty())
                        <div class="text-sm text-muted-500">
                             {{ __('substaff_permission.no_permissions') }}
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                            @foreach ($permissions as $permission)
                                @php
                                    $permName = $permission->name;
                                    $isChecked = in_array($permName, $directPermissionNames, true);
                                @endphp

                                <label for="perm-{{ $user->id }}-{{ str_replace('.', '-', $permName) }}"
                                       class="flex items-center gap-3 p-3 rounded-xl border border-muted-100 hover:bg-muted-50 cursor-pointer transition-colors group">

                                    <div class="relative flex items-center">
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $permName }}"
                                               id="perm-{{ $user->id }}-{{ str_replace('.', '-', $permName) }}"
                                               class="peer h-5 w-5 rounded-md border-muted-300 text-primary focus:ring-primary/20 transition-all cursor-pointer"
                                               {{ $isChecked ? 'checked' : '' }}>
                                    </div>

                                    <span class="text-sm font-medium text-muted-600 group-hover:text-main transition-colors select-none">
                                         {{ __('substaff_permission.' . str_replace('.', '_', $permName)) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    {{-- Submit Button --}}
                    <div class="flex items-center justify-end w-full pt-2 border-t border-muted-100">
                        <button type="submit"
                                class="flex gap-2 items-center justify-center px-6 py-3 bg-primary hover:bg-primary-hover text-white font-medium rounded-xl shadow-lg shadow-primary/20 transition-all active:scale-[0.98]">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 lucide lucide-pencil-icon lucide-pencil">
                                <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                                <path d="m15 5 4 4"/>
                            </svg>
                            {{ __('substaff_permission.update_button') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
