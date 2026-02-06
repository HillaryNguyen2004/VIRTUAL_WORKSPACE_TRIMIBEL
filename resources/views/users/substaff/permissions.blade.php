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
        <div class="flex items-center gap-4 mb-4">
            @include('components.back-btn' , ['route' => $dashRoute])

            <div class="flex-1">
                <h2 class="font-bold text-3xl text-main tracking-tight">
                    {{ __('user_permission.title') ?? 'Substaff Permissions' }}
                </h2>
                <p class="text-muted-500 text-sm mt-2">
                    {{ __('user_permission.subtitle') ?? 'Manage permissions for a specific substaff user' }}
                </p>

                @if($user)
                    <div class="mt-2 text-sm text-muted-500">
                        Editing:
                        <span class="font-semibold text-main">{{ $user->name }}</span>
                        <span class="text-muted-400">({{ $user->email }})</span>
                    </div>
                @endif
            </div>

            {{-- Optional: quick jump back to team section on staff dashboard --}}
            @can('staff.substaff.create')
                @if(Route::has('staff.dashboard'))
                    <a href="{{ route('staff.dashboard') }}#team-members"
                       class="px-3 py-2 text-sm rounded-xl border border-muted-200 bg-white hover:bg-muted-50 transition">
                        Create substaff
                    </a>
                @endif
            @endcan
        </div>

        {{-- Guard: if no user passed --}}
        @if(!$user)
            <div class="bg-danger/10 text-danger border border-danger/20 text-sm font-medium px-4 py-3 rounded-xl">
                Missing substaff user.
            </div>
        @else

            {{-- SUCCESS ALERT --}}
            @if(session('success'))
                <div class="bg-secondary/10 text-secondary border border-secondary/20 text-sm font-medium px-4 py-3 rounded-xl w-full animate-fade-in-up flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            {{-- MAIN CARD --}}
            <div class="grid gap-6 animate-fade-in-up [animation-delay:150ms]">
                <form action="{{ route('staff.substaff.permissions.update', $user) }}" method="POST"
                      class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 p-6 hover:border-primary/30 transition-all">
                    @csrf

                    {{-- Card Header --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 border-b border-muted-100 pb-4">
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
                    </div>

                    {{-- Permissions Grid --}}
                    @if($permissions->isEmpty())
                        <div class="text-sm text-muted-500">
                            No permissions available for you to grant.
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
                                        {{ __('user_permission.' . str_replace('.', '_', $permName)) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    {{-- Action Footer --}}
                    <div class="flex items-center justify-end w-full pt-2 border-t border-muted-100">
                        <button type="submit"
                                class="flex gap-2 items-center justify-center px-6 py-2.5 bg-primary hover:bg-primary-hover text-white font-medium rounded-xl shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                                <path d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7zM160 128C107 128 64 171 64 224L64 480C64 533 107 576 160 576L416 576C469 576 512 533 512 480L512 384C512 366.3 497.7 352 480 352C462.3 352 448 366.3 448 384L448 480C448 497.7 433.7 512 416 512L160 512C142.3 512 128 497.7 128 480L128 224C128 206.3 142.3 192 160 192L256 192C273.7 192 288 177.7 288 160C288 142.3 273.7 128 256 128L160 128z"/>
                            </svg>
                            {{ __('user_permission.update_button') ?? 'Update permissions' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
