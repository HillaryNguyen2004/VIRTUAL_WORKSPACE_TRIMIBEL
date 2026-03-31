<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\FilterUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\ImportUserRequest;
use App\Http\Requests\UpdateUserPermissionsRequest;

use App\Repositories\UserRepositoryInterface;
use App\Repositories\UserPermissionRepositoryInterface;

use App\Services\UserService;
use App\Services\UserImportService;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// NOT REFACTORED YET
class UserController extends Controller
{
    protected $userRepo;
    protected $permissionRepo;
    protected $userService;
    protected $importService;

    public function __construct(
        UserService $userService,
        UserRepositoryInterface $userRepo,
        UserPermissionRepositoryInterface $permissionRepo,
        UserImportService $importService
    ) {
        $this->userRepo = $userRepo;
        $this->permissionRepo = $permissionRepo;
        $this->userService = $userService;
        $this->importService = $importService;
    }

    public function index(FilterUserRequest $request)
    {
        $users = $this->userRepo->filterUsers($request->filters());
        $allUsers = User::all();

        // Group potential members (only 'user', 'substaff', 'staff' role can be teammates)
        $potentialMembers = $allUsers->filter(fn($u) => $u->hasAnyRole(['user', 'substaff', 'staff']));

        $availableUsers = [];
        $teamMembersByStaff = [];

        foreach ($allUsers as $person) {
            $isLeaderRole = $person->hasRole(['staff', 'substaff']);

            // 1. Available users for this person (potential leader)
            $availableUsers[$person->id] = $potentialMembers
                ->filter(
                    fn($u) =>
                    $u->id !== $person->id &&
                    (empty($u->team_leader_id) || (int) $u->team_leader_id === (int) $person->id)
                )
                ->values()
                ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
                ->all();

            // 2. Current team members (if they are a leader)
            if ($isLeaderRole) {
                $teamMembersByStaff[$person->id] = $allUsers
                    ->filter(fn($u) => (int) $u->team_leader_id === (int) $person->id)
                    ->values()
                    ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
                    ->all();
            }
        }

        return view('users.index', compact('users', 'allUsers', 'availableUsers', 'teamMembersByStaff'));
    }

    /**
     * ✅ FIXED: Enforce waterfall rule before changing user role.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        // role might be "role" or "roles" depending on your form/request.
        // Your service uses $data['role'], so we keep that.
        $roleName = $data['role'] ?? null;

        if ($roleName) {
            $this->authorize('assignRole', [$user, $roleName]);
        }

        $this->userService->updateUser($user, $data);

        return redirect()->route('admin.users.index')->with('success', __('messages.user_updated'));
    }

    public function destroy(User $user)
    {
        $deleted = $this->userService->deleteUser($user);

        if (!$deleted) {
            return back()->with('error', __('messages.user_not_deleted'));
        }

        return redirect()->route('admin.users.index')->with('success', __('messages.user_deleted'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('users.create', compact('departments'));
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validatedData());

        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('admin.users.create')->with('success', __('messages.user_created'));
    }

    /**
     * ✅ IMPORTANT: This page edits ROLE permissions globally (role_has_permissions).
     * Only allow admin to access it.
     */
    public function permissions()
    {
        // abort_unless(auth()->user()->hasRole('admin'), 403);

        $departments = Department::orderBy('name')->get();

        $userRole = Role::where('name', 'user')->first();
        $staffRole = Role::where('name', 'staff')->first();

        // Permissions to DISPLAY in each card (from role_has_permissions)
        $permissionsByRoleId = [];

        if ($userRole) {
            $permissionsByRoleId[$userRole->id] = Permission::query()
                ->select('permissions.*')
                ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('role_has_permissions.role_id', $userRole->id)
                ->orderBy('permissions.name')
                ->get();
        }

        if ($staffRole) {
            $permissionsByRoleId[$staffRole->id] = Permission::query()
                ->select('permissions.*')
                ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('role_has_permissions.role_id', $staffRole->id)
                ->orderBy('permissions.name')
                ->get();
        }

        return view('users.permissions', compact(
            'departments',
            'userRole',
            'staffRole',
            'permissionsByRoleId'
        ));
    }

    /**
     * ✅ IMPORTANT: This updates ROLE permissions globally.
     * Only allow admin.
     */
    public function updatePermissions(UpdateUserPermissionsRequest $request)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $permissionNames = $request->input('permissions', []);
        if (!is_array($permissionNames)) {
            $permissionNames = [];
        }

        $this->permissionRepo->updateRolePermissions(
            $request->role_name,
            $permissionNames
        );

        // Clear Spatie permission cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->back()->with('success', __('messages.permissions_updated'));
    }

    // ==========================
    // ✅ NEW: Per-user permissions (direct permissions) for subadmin limits
    // ==========================

    /**
     * Show direct permission editor for a specific user.
     * Actor only sees permissions they can grant (subset of their own).
     */
    public function editUserPermissions(User $user)
    {
        // Optional: forbid editing admin’s permissions, etc.
        // if ($user->hasRole('admin')) abort(403);

        $grantableNames = auth()->user()->getAllPermissions()->pluck('name')->values()->all();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $grantableNames)
            ->orderBy('name')
            ->get();

        return view('users.user_permissions', [
            'user' => $user,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update direct permissions of a user (model_has_permissions).
     * ✅ Enforced by policy: cannot grant perms you don't have, and must be higher level than target.
     */
    public function updateUserPermissions(Request $request, User $user)
    {
        $permissionNames = $request->input('permissions', []);
        if (!is_array($permissionNames)) {
            $permissionNames = [];
        }

        $this->authorize('syncPermissions', [$user, $permissionNames]);

        // Direct permissions on the user
        $user->syncPermissions($permissionNames);

        // Clear Spatie permission cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'User permissions updated');
    }

    // ==========================
    // ✅ Subadmin management pages (NEW)
    // ==========================

    public function subadminIndex(Request $request)
    {
        // You can restrict this to admin only if you want
        // abort_unless(auth()->user()->hasRole('admin'), 403);

        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->whereDoesntHave('roles', function ($r) {
                $r->where('name', 'admin'); // don't allow making admin into subadmin
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.subadmins.index', compact('users', 'q'));
    }

    public function makeSubadmin(User $user)
    {
        // Waterfall check: can current user assign subadmin role?
        $this->authorize('assignRole', [$user, 'subadmin']);

        // Convert user to subadmin role
        $user->syncRoles(['subadmin']);

        // OPTIONAL: start with empty direct permissions (recommended)
        // so each subadmin is purely custom-limited:
        $user->syncPermissions([]);

        // Clear Spatie permission cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.subadmins.permissions.edit', $user)
            ->with('success', 'Subadmin created. Now choose permissions.');
    }

    public function editSubadminPermissions(User $user)
    {
        // Must be subadmin to edit on this page
        if (!$user->hasRole('subadmin')) {
            return redirect()
                ->route('admin.subadmins.index')
                ->with('error', 'This user is not a subadmin. Please create subadmin first.');
        }

        $grantableNames = auth()->user()->getAllPermissions()->pluck('name')->values()->all();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $grantableNames)
            ->orderBy('name')
            ->get();

        return view('users.subadmins.permissions', [
            'user' => $user,
            'permissions' => $permissions,
        ]);
    }

    public function updateSubadminPermissions(Request $request, User $user)
    {
        if (!$user->hasRole('subadmin')) {
            return redirect()
                ->route('admin.subadmins.index')
                ->with('error', 'This user is not a subadmin.');
        }

        $permissionNames = $request->input('permissions', []);
        if (!is_array($permissionNames)) {
            $permissionNames = [];
        }

        // Waterfall: cannot grant perms you don’t have + cannot edit equal/higher level
        $this->authorize('syncPermissions', [$user, $permissionNames]);

        // Save direct permissions
        $user->syncPermissions($permissionNames);
        // Clear Spatie permission cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        return back()->with('success', 'Subadmin permissions updated');
    }

    public function makeSubstaff(User $user)
    {
        $this->authorize('assignRole', [$user, 'substaff']);

        $user->syncRoles(['substaff']);
        $user->syncPermissions([]); // start empty

        // Clear Spatie permission cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('staff.substaff.permissions.edit', $user)
            ->with('success', 'Substaff created. Now choose permissions.');
    }

    public function editSubstaffPermissions(User $user)
    {
        if (!$user->hasRole('substaff')) {
            return back()->with('error', 'This user is not substaff.');
        }

        $grantableNames = auth()->user()->delegatablePermissionNames();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $grantableNames)
            ->orderBy('name')
            ->get();

        return view('users.substaff.permissions', compact('user', 'permissions'));
    }

    public function updateSubstaffPermissions(Request $request, User $user)
    {
        if (!$user->hasRole('substaff')) {
            return back()->with('error', 'This user is not substaff.');
        }

        $permissionNames = $request->input('permissions', []);
        if (!is_array($permissionNames))
            $permissionNames = [];

        // ✅ Waterfall enforced by your policy
        $this->authorize('syncPermissions', [$user, $permissionNames]);

        $user->syncPermissions($permissionNames);

        // Clear Spatie permission cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Substaff permissions updated');
    }

    public function demoteSubstaff(User $user)
    {
        // Optional: Ensure the user actually has the substaff role before demoting
        if (!$user->hasRole('substaff')) {
            return back()->with('error', 'This user is not substaff.');
        }

        $this->authorize('assignRole', [$user, 'substaff']);

        $user->syncRoles(['user']); 
        $user->syncPermissions([]);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'User has been demoted to a standard user.');
    }

    public function updateDepartmentRolePermissions(Request $request, Department $department, Role $role)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        // Restrict to only user/staff if you want
        abort_unless(in_array($role->name, ['user', 'staff'], true), 404);

        $permissionIds = $request->input('permissions', []);
        if (!is_array($permissionIds))
            $permissionIds = [];

        $permissionIds = Permission::whereIn('id', $permissionIds)->pluck('id')->all();

        DB::transaction(function () use ($department, $role, $permissionIds) {
            DB::table('department_role_permissions')
                ->where('department_id', $department->id)
                ->where('role_id', $role->id)
                ->delete();

            if (!empty($permissionIds)) {
                $rows = array_map(fn($pid) => [
                    'department_id' => $department->id,
                    'role_id' => $role->id,
                    'permission_id' => $pid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $permissionIds);

                DB::table('department_role_permissions')->insert($rows);
            }
        });

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', "{$department->name} - {$role->name} permissions updated.");
    }


    // ==========================
    // Import/Template
    // ==========================

    public function downloadTemplate()
    {
        return $this->importService->downloadTemplate();
    }

    public function import(ImportUserRequest $request)
    {
        $importedCount = $this->importService->importFromCsv($request->file('csv_file'));

        return redirect()->back()->with('success', __('messages.user_imported') . " ({$importedCount} users)");
    }
}
