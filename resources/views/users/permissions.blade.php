@extends('layout_dashboard')

@section('content')
    @php
        use Illuminate\Support\Facades\Route;
        use Illuminate\Support\Facades\DB;

        // Determine dashboard route based on role
        $dashRoute = 'user.dashboard';
        if (auth()->user()->hasRole('admin') && Route::has('admin.dashboard')) {
            $dashRoute = 'admin.dashboard';
        } elseif (auth()->user()->hasRole('subadmin') && Route::has('subadmin.dashboard')) {
            $dashRoute = 'subadmin.dashboard';
        } elseif (auth()->user()->hasRole('staff') && Route::has('staff.dashboard')) {
            $dashRoute = 'staff.dashboard';
        } elseif (auth()->user()->hasRole('substaff') && Route::has('substaff.dashboard')) {
            $dashRoute = 'substaff.dashboard';
        }

        // Safety defaults
        $permissions = $permissions ?? collect();
        $departments = $departments ?? collect();
        $userRole = $userRole ?? null;   // Spatie Role model
        $staffRole = $staffRole ?? null;  // Spatie Role model

        // Selected department
        $selectedDepartmentId = old('department_id', request('department_id'));
        $selectedDepartment = $departments->firstWhere('id', (int) $selectedDepartmentId);

        // Roles to display (User + Staff)
        $rolesToShow = collect([$userRole, $staffRole])->filter();

        // Get checked permissions for (department_id, role_id)
        $getCheckedIds = function ($deptId, $roleId) {
            if (!$deptId || !$roleId)
                return [];
            return DB::table('department_role_permissions')
                ->where('department_id', $deptId)
                ->where('role_id', $roleId)
                ->pluck('permission_id')
                ->toArray();
        };

        // Count users in a department having a given role name
        $countUsersByRoleName = function ($deptId, $roleName) {
            if (!$deptId || !$roleName)
                return 0;
            return \App\Models\User::where('department_id', $deptId)
                ->whereHas('roles', fn($q) => $q->where('name', $roleName))
                ->count();
        };
    @endphp

    <div class="flex flex-col gap-6 w-full w-max-[1200px] mx-auto text-main px-4 md:px-8 lg:px-16 xl:px-24 py-8">

        {{-- HEADER SECTION --}}
        <div class="flex items-center gap-4 mb-2">
            @include('components.back-btn', ['route' => $dashRoute])

            <div class="flex-1">
                <h2 class="font-bold text-3xl text-main tracking-tight">
                    {{ __('user_permission.title') ?? 'Permissions' }}
                </h2>
                <p class="text-muted-500 text-sm mt-2">
                    {{ __('user_permission.subtitle') ?? 'Manage department-based access controls' }}
                </p>
            </div>

            @can('admin.departments.view')
            <a href="{{ route('admin.departments.index') }}"
                class="flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-xl transition-all shadow-lg shadow-primary/20">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 lucide lucide-network-icon lucide-network"><rect x="16" y="16" width="6" height="6" rx="1"/><rect x="2" y="16" width="6" height="6" rx="1"/><rect x="9" y="2" width="6" height="6" rx="1"/><path d="M5 16v-3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3"/><path d="M12 12V8"/></svg>
                <span class="font-medium">Edit Departments</span>
            </a>
            @endcan

            <!-- @can('admin.roles.view')
                <a href="{{ route('admin.subadmins.index') }}"
                    class="px-3 py-2 text-sm rounded-xl border border-muted-200 bg-white hover:bg-muted-50 transition">
                    Create subadmin
                </a>
            @endcan -->
        </div>

        {{-- SUCCESS ALERT --}}
        @if(session('success'))
            <div
                class="bg-secondary/10 text-secondary border border-secondary/20 text-sm font-medium px-4 py-3 rounded-xl w-full animate-fade-in-up flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- DEPARTMENT PICKER (matches screenshot top card) --}}
        <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 p-6">
            <form method="GET" action="{{ url()->current() }}" class="flex flex-col sm:flex-row gap-3 sm:items-center">
                <div class="flex-1">
                    <label class="text-sm font-semibold text-muted-600">Select Department</label>
                    <select name="department_id" class="mt-2 w-full rounded-xl border border-muted-200 bg-white px-3 py-3">
                        <option value="">-- Choose department --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (string) $dept->id === (string) $selectedDepartmentId ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>

                    <p class="text-xs text-muted-500 mt-3">
                        Select a department to configure permissions for <b>User</b> and <b>Staff</b> roles in that
                        department.
                    </p>
                </div>

                <button type="submit"
                    class="px-6 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary-hover transition">
                    Load
                </button>
            </form>
        </div>

        {{-- EMPTY STATE --}}
        @if(!$selectedDepartment)
            <div class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 p-6 text-sm text-muted-500">
                Choose a department to edit its permissions.
            </div>
        @else
            {{-- ROLE CARDS: Department - User, Department - Staff --}}
            <div class="grid gap-6 animate-fade-in-up [animation-delay:150ms]">
                @foreach($rolesToShow as $role)
                    @php
                        $checkedIds = $getCheckedIds($selectedDepartment->id, $role->id);
                        $roleUserCount = $countUsersByRoleName($selectedDepartment->id, $role->name);
                    @endphp

                    <form action="{{ route('admin.departments.roles.permissions.update', [$selectedDepartment, $role]) }}"
                        method="POST"
                        class="bg-white rounded-2xl border border-muted-200 shadow-lg shadow-main/5 p-6 hover:border-primary/30 transition-all">
                        @csrf

                        {{-- Card Header (matches screenshot) --}}
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 border-b border-muted-100 pb-4">
                            <div>
                                <h5 class="text-2xl font-bold text-main flex items-center gap-2">
                                    {{ $selectedDepartment->name }} - {{ ucfirst($role->name) }}
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary ring-1 ring-inset ring-primary/20">
                                        {{ $roleUserCount }} users
                                    </span>
                                </h5>
                                <p class="text-sm text-muted-500 mt-1">
                                    Configure permissions for users with "{{ ucfirst($role->name) }}" role in this department
                                </p>
                            </div>

                            <div class="text-xs text-muted-500">
                                Applied to all users in this department
                            </div>
                        </div>

                        {{-- Permissions Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                            @foreach (($permissionsByRoleId[$role->id] ?? collect()) as $permission)
                                @php 
                                    $isChecked = in_array($permission->id, $checkedIds, true);
                                    $canEditRolePermissions = auth()->user()->can('admin.roles.edit');
                                @endphp

                                <label for="perm-{{ $selectedDepartment->id }}-{{ $role->id }}-{{ $permission->id }}"
                                    class="flex items-center gap-3 p-3 rounded-xl border border-muted-100 hover:bg-muted-50 cursor-pointer transition-colors group">
                                    <div class="relative flex items-center">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                            id="perm-{{ $selectedDepartment->id }}-{{ $role->id }}-{{ $permission->id }}"
                                            class="w-5 h-5 rounded-md border-muted-300 text-primary focus:ring-primary/20 cursor-pointer"
                                            @if($isChecked) checked @endif
                                            @disabled(!$canEditRolePermissions)>
                                    </div>

                                    <span
                                        class="text-sm font-medium text-muted-600 group-hover:text-main transition-colors select-none">
                                        {{ __('user_permission.' . str_replace('.', '_', $permission->name)) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        {{-- Action Footer --}}
                        @can('admin.roles.edit')
                            <div class="flex items-center justify-end w-full pt-2 border-t border-muted-100">
                                <button type="submit"
                                    class="flex gap-2 items-center justify-center px-6 py-2.5 bg-primary hover:bg-primary-hover text-white font-medium rounded-xl shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-4 h-4 fill-current">
                                        <path
                                            d="M535.6 85.7C513.7 63.8 478.3 63.8 456.4 85.7L432 110.1L529.9 208L554.3 183.6C576.2 161.7 576.2 126.3 554.3 104.4L535.6 85.7zM236.4 305.7C230.3 311.8 225.6 319.3 222.9 327.6L193.3 416.4C190.4 425 192.7 434.5 199.1 441C205.5 447.5 215 449.7 223.7 446.8L312.5 417.2C320.7 414.5 328.2 409.8 334.4 403.7L496 241.9L398.1 144L236.4 305.7zM160 128C107 128 64 171 64 224L64 480C64 533 107 576 160 576L416 576C469 576 512 533 512 480L512 384C512 366.3 497.7 352 480 352C462.3 352 448 366.3 448 384L448 480C448 497.7 433.7 512 416 512L160 512C142.3 512 128 497.7 128 480L128 224C128 206.3 142.3 192 160 192L256 192C273.7 192 288 177.7 288 160C288 142.3 273.7 128 256 128L160 128z" />
                                    </svg>
                                    Update Permissions
                                </button>
                            </div>
                        @endcan
                    </form>
                @endforeach
            </div>
        @endif

    </div>
@endsection