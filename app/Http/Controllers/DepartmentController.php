<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with(['users' => function ($query) {
            $query->whereHas('roles', function ($roleQuery) {
                $roleQuery->where('name', 'staff');
            })->orderBy('name');
        }])->orderBy('name')->get();

        $staffUsers = User::role('staff')
            ->with('department')
            ->orderBy('name')
            ->get();

        return view('departments.index', compact('departments', 'staffUsers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
        ]);

        Department::create($data);

        return redirect()->route('admin.departments.index')->with('success', 'Tạo phòng ban thành công.');
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
        ]);

        $department->update($data);

        return redirect()->route('admin.departments.index')->with('success', 'Cập nhật phòng ban thành công.');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('admin.departments.index')->with('success', 'Xóa phòng ban thành công.');
    }

    public function assignStaff(Request $request, Department $department)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($data['user_id']);

        if (!$user->hasRole('staff')) {
            return redirect()->route('admin.departments.index')->with('error', 'Chỉ được gán nhân viên (staff) vào phòng ban.');
        }

        $user->department_id = $department->id;
        $user->save();

        return redirect()->route('admin.departments.index')->with('success', 'Đã gán nhân viên vào phòng ban.');
    }

    public function removeStaff(Department $department, User $user)
    {
        if (!$user->hasRole('staff')) {
            return redirect()->route('admin.departments.index')->with('error', 'Chỉ được thao tác với nhân viên (staff).');
        }

        if ($user->department_id !== $department->id) {
            return redirect()->route('admin.departments.index')->with('error', 'Nhân viên không thuộc phòng ban này.');
        }

        $user->department_id = null;
        $user->save();

        return redirect()->route('admin.departments.index')->with('success', 'Đã gỡ nhân viên khỏi phòng ban.');
    }

    // public function editPermissions(Department $department)
    // {
    //     $permissions = Permission::where('guard_name', 'web')->orderBy('name')->get();
    //     $selected = $department->permissions()->pluck('permissions.id')->toArray();

    //     return view('departments.permissions', compact('department', 'permissions', 'selected'));
    // }

    // public function updatePermissions(Request $request, Department $department)
    // {
    //     $permissionIds = $request->input('permissions', []);
    //     if (!is_array($permissionIds)) $permissionIds = [];

    //     // save template
    //     $department->permissions()->sync($permissionIds);

    //     // apply to all users (staff + user) in this department except admin
    //     $users = User::query()
    //         ->where('department_id', $department->id)
    //         ->whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
    //         ->get();

    //     foreach ($users as $u) {
    //         $u->syncDepartmentAddonPermissions();
    //     }

    //     app(PermissionRegistrar::class)->forgetCachedPermissions();

    //     return back()->with('success', 'Department permissions saved and applied to users.');
    // }


    public function updatePermissions(Request $request, Department $department)
    {
        $permissionIds = $request->input('permissions', []);
        if (!is_array($permissionIds)) $permissionIds = [];

        // save template
        $department->permissions()->sync($permissionIds);

        // apply to all users in this department except admin
        $users = User::query()
            ->where('department_id', $department->id)
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
            ->get();

        foreach ($users as $u) {
            $u->syncDepartmentAddonPermissions();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ✅ Redirect back to the same 2-tab page and keep the tab + selected department
        return redirect()
            ->route('admin.permissions', [
                'tab' => 'departments',
                'department_id' => $department->id,
            ])
            ->with('success', 'Department permissions saved and applied to users.');
    }
}
