<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

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
}
