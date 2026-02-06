@extends('layout_dashboard')

@section('content')
    @php
        use Illuminate\Support\Facades\Route;

        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        }

        // Safety defaults (avoid undefined)
        $roles = $roles ?? collect();
        $permissions = $permissions ?? collect();
        $departments = $departments ?? collect(); // Controller should pass: Department::with('permissions')->get()
        $selectedDepartmentId = old('department_id', request('department_id'));

        $activeTab = request('tab', 'roles'); // roles | departments

        // For department tab: selected department + its selected permission names
        $selectedDepartment = $departments->firstWhere('id', (int) $selectedDepartmentId);
        $departmentPermissionIds = $selectedDepartment
            ? ($selectedDepartment->permissions?->pluck('id')->toArray() ?? [])
            : [];
    @endphp

    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- HEADER SECTION --}}
        <div class="flex items-center gap-4 mb-2">
            @include('components.back-btn' , ['route' => $dashRoute])

            <div class="flex-1">
                <h2 class="font-bold text-3xl text-main tracking-tight">
                    {{ __('user_permission.title') ?? 'Permissions' }}
                </h2>
                <p class="text-muted-500 text-sm mt-2">
                    {{ __('user_permission.subtitle') ?? 'Manage role-based and department add-on permissions' }}
                </p>
            </div>

            <a href="{{ route('admin.subadmins.index') }}"
               class="px-3 py-2 text-sm rounded-xl border border-muted-200 bg-white hover:bg-muted-50 transition">
                Create subadmin
            </a>
        </div>

        {{-- TABS --}}
        <div class="flex items-center gap-2">
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'roles']) }}"
               class="px-4 py-2 rounded-xl text-sm font-semibold border transition
                    {{ $activeTab === 'roles' ? 'bg-primary text-white border-primary' : 'bg-white border-muted-200 hover:bg-muted-50' }}">
                General Roles
            </a>

            <a href="{{ request()->fullUrlWithQuery(['tab' => 'departments']) }}"
               class="px-4 py-2 rounded-xl text-sm font-semibold border transition
                    {{ $activeTab === 'departments' ? 'bg-primary text-white border-primary' : 'bg-white border-muted-200 hover:bg-muted-50' }}">
                Department Add-on
            </a>
        </div>

        {{-- SUCCESS ALERT --}}
        @if(session('success'))
            <div class="bg-secondary/10 text-secondary border border-secondary/20 text-sm font-medium px-4 py-3 rounded-xl w-full animate-fade-in-up flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- =========================================================
             TAB 1: GENERAL ROLE PERMISSIONS (same layout)
        ========================================================== --}}
        @if($activeTab === 'roles')
            <div class="grid gap-6 animate-fade-in-up [animation-delay:150ms]">
                @foreach ($roles as $role)
                    @if ($role->name !== 'admin' && $role->name !== 'subadmin' && $role->name !== 'substaff')
                        <form action="{{ route('admin.permissions.update') }}" method="POST"
                              class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 p-6 hover:border-primary/30 transition-all">
                            @csrf

                            {{-- Hidden Role Name --}}
                            <input type="hidden" name="role_name" value="{{ $role->name }}">

                            {{-- Card Header --}}
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 border-b border-muted-100 pb-4">
                                <div>
                                    <h5 class="text-xl font-bold text-main flex items-center gap-2">
                                        {{ ucfirst($role->name) }}
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary ring-1 ring-inset ring-primary/20">
                                            {{ \App\Models\User::role($role->name)->whereHas('roles', function ($q) {
                                                $q->where('name', '!=', 'admin');
                                            })->count() }} {{ __('users') ?? 'users' }}
                                        </span>
                                    </h5>
                                    <p class="text-sm text-muted-500 mt-1">Configure permissions for this role</p>
                                </div>
                            </div>

                            {{-- Permissions Grid --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                                @foreach ($permissions as $permission)
                                    <label for="perm-role-{{ $role->id }}-{{ str_replace('.', '-', $permission->name) }}"
                                           class="flex items-center gap-3 p-3 rounded-xl border border-muted-100 hover:bg-muted-50 cursor-pointer transition-colors group">

                                        <div class="relative flex items-center">
                                            <input type="checkbox"
                                                   name="permissions[]"
                                                   value="{{ $permission->name }}"
                                                   id="perm-role-{{ $role->id }}-{{ str_replace('.', '-', $permission->name) }}"
                                                   class="w-5 h-5 rounded-md border-muted-300 text-primary focus:ring-primary/20 cursor-pointer"
                                                   @if($role->hasPermissionTo($permission->name)) checked @endif>
                                        </div>

                                        <span class="text-sm font-medium text-muted-600 group-hover:text-main transition-colors select-none">
                                            {{ __('user_permission.' . str_replace('.', '_', $permission->name)) }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            {{-- Action Footer --}}
                            <div class="flex items-center justify-end w-full pt-2 border-t border-muted-100">
                                <button type="submit"
                                        class="flex gap-2 items-center justify-center px-6 py-2.5 bg-primary hover:bg-primary-hover text-white font-medium rounded-xl shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                                        <path d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7zM160 128C107 128 64 171 64 224L64 480C64 533 107 576 160 576L416 576C469 576 512 533 512 480L512 384C512 366.3 497.7 352 480 352C462.3 352 448 366.3 448 384L448 480C448 497.7 433.7 512 416 512L160 512C142.3 512 128 497.7 128 480L128 224C128 206.3 142.3 192 160 192L256 192C273.7 192 288 177.7 288 160C288 142.3 273.7 128 256 128L160 128z"/>
                                    </svg>
                                    {{ __('user_permission.update_button') ?? 'Update' }}
                                </button>
                            </div>
                        </form>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- =========================================================
             TAB 2: DEPARTMENT ADD-ON PERMISSIONS (same layout)
        ========================================================== --}}
        @if($activeTab === 'departments')
            {{-- Department picker --}}
            <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 p-6">
                <form method="GET" action="{{ url()->current() }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    <input type="hidden" name="tab" value="departments">

                    <div class="flex-1">
                        <label class="text-sm font-semibold text-muted-600">Select Department</label>
                        <select name="department_id"
                                class="mt-1 w-full rounded-xl border border-muted-200 bg-white px-3 py-2">
                            <option value="">-- Choose department --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ (string)$dept->id === (string)$selectedDepartmentId ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button class="px-4 py-2 rounded-xl bg-primary text-white font-semibold hover:bg-primary-hover transition">
                        Load
                    </button>
                </form>

                <p class="text-xs text-muted-500 mt-3">
                    Department permissions are an <b>add-on</b>. Users still keep their role permissions as base.
                </p>
            </div>

            {{-- Department permission card (same role layout) --}}
            @if(!$selectedDepartment)
                <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 p-6 text-sm text-muted-500">
                    Choose a department to edit its add-on permissions.
                </div>
            @else
                <div class="grid gap-6 animate-fade-in-up [animation-delay:150ms]">
                    {{-- NOTE: change this route to your own department update route --}}
                    <form action="{{ route('admin.departments.permissions.update', $selectedDepartment) }}" method="POST"
                          class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 p-6 hover:border-primary/30 transition-all">
                        @csrf

                        {{-- Card Header --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 border-b border-muted-100 pb-4">
                            <div>
                                <h5 class="text-xl font-bold text-main flex items-center gap-2">
                                    {{ $selectedDepartment->name }}
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary ring-1 ring-inset ring-primary/20">
                                        {{ \App\Models\User::where('department_id', $selectedDepartment->id)->count() }} {{ __('users') ?? 'users' }}
                                    </span>
                                </h5>
                                <p class="text-sm text-muted-500 mt-1">Configure add-on permissions for this department</p>
                            </div>

                            <div class="text-xs text-muted-500">
                                These will be applied to all users in the department.
                            </div>
                        </div>

                        {{-- Permissions Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                            @foreach ($permissions as $permission)
                                @php
                                    $isChecked = in_array($permission->id, $departmentPermissionIds, true);
                                @endphp

                                <label for="perm-dept-{{ $selectedDepartment->id }}-{{ str_replace('.', '-', $permission->name) }}"
                                       class="flex items-center gap-3 p-3 rounded-xl border border-muted-100 hover:bg-muted-50 cursor-pointer transition-colors group">

                                    <div class="relative flex items-center">
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $permission->id }}"
                                               id="perm-dept-{{ $selectedDepartment->id }}-{{ str_replace('.', '-', $permission->name) }}"
                                               class="w-5 h-5 rounded-md border-muted-300 text-primary focus:ring-primary/20 cursor-pointer"
                                               @if($isChecked) checked @endif>
                                    </div>

                                    <span class="text-sm font-medium text-muted-600 group-hover:text-main transition-colors select-none">
                                        {{ __('user_permission.' . str_replace('.', '_', $permission->name)) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        {{-- Action Footer --}}
                        <div class="flex items-center justify-end w-full pt-2 border-t border-muted-100">
                            <button type="submit"
                                    class="flex gap-2 items-center justify-center px-6 py-2.5 bg-primary hover:bg-primary-hover text-white font-medium rounded-xl shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                                    <path d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7zM160 128C107 128 64 171 64 224L64 480C64 533 107 576 160 576L416 576C469 576 512 533 512 480L512 384C512 366.3 497.7 352 480 352C462.3 352 448 366.3 448 384L448 480C448 497.7 433.7 512 416 512L160 512C142.3 512 128 497.7 128 480L128 224C128 206.3 142.3 192 160 192L256 192C273.7 192 288 177.7 288 160C288 142.3 273.7 128 256 128L160 128z"/>
                                </svg>
                                {{ __('user_permission.update_button') ?? 'Update' }}
                            </button>
                        </div>

                        {{-- Keep tab + department selected after POST (if your controller redirects back) --}}

                    </form>
                </div>
            @endif
        @endif

    </div>
@endsection
