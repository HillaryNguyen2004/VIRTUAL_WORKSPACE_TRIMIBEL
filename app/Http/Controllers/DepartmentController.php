<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentService;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Requests\AssignStaffRequest;
use App\Http\Requests\RemoveStaffRequest;
use App\Http\Requests\UpdateDepartmentPermissionsRequest;
use Illuminate\Support\Facades\Log;

class DepartmentController extends Controller
{
    protected DepartmentService $departmentService;

    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService = $departmentService;
    }

    /**
     * Display all departments with their users and available staff
     */
    public function index()
    {
        try {
            $data = $this->departmentService->getDepartmentsWithUsers();

            return view('departments.index', $data);
        } catch (\Exception $e) {
            Log::error('Index Departments Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Không thể tải danh sách phòng ban.');
        }
    }

    /**
     * Create a new department
     */
    public function store(StoreDepartmentRequest $request)
    {
        try {
            $this->departmentService->createDepartment($request->validated());

            return redirect()->route('admin.departments.index')->with('success', 'Tạo phòng ban thành công.');
        } catch (\Exception $e) {
            Log::error('Store Department Error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Lỗi tạo phòng ban.');
        }
    }

    /**
     * Update a department
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        try {
            $this->departmentService->updateDepartment($department, $request->validated());

            return redirect()->route('admin.departments.index')->with('success', 'Cập nhật phòng ban thành công.');
        } catch (\Exception $e) {
            Log::error('Update Department Error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Lỗi cập nhật phòng ban.');
        }
    }

    /**
     * Delete a department
     */
    public function destroy(Department $department)
    {
        try {
            $this->departmentService->deleteDepartment($department);

            return redirect()->route('admin.departments.index')->with('success', 'Xóa phòng ban thành công.');
        } catch (\Exception $e) {
            Log::error('Destroy Department Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Lỗi xóa phòng ban.');
        }
    }

    /**
     * Assign staff to a department
     */
    public function assignStaff(AssignStaffRequest $request, Department $department)
    {
        try {
            $this->departmentService->assignStaffToDepartment($request->user_id, $department->id);

            return redirect()->route('admin.departments.index')->with('success', 'Đã gán nhân viên vào phòng ban.');
        } catch (\Exception $e) {
            Log::error('Assign Staff Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Lỗi gán nhân viên.');
        }
    }

    /**
     * Remove staff from a department
     */
    public function removeStaff(Department $department, User $user)
    {
        try {
            $this->departmentService->removeStaffFromDepartment($department, $user);

            return redirect()->route('admin.departments.index')->with('success', 'Đã gỡ nhân viên khỏi phòng ban.');
        } catch (\Exception $e) {
            Log::error('Remove Staff Error: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update department permissions
     */
    public function updatePermissions(UpdateDepartmentPermissionsRequest $request, Department $department)
    {
        try {
            $permissionIds = $request->input('permissions', []);
            $this->departmentService->updateDepartmentPermissions($department, $permissionIds);

            return redirect()
                ->route('admin.permissions', [
                    'tab' => 'departments',
                    'department_id' => $department->id,
                ])
                ->with('success', 'Cập nhật quyền hạn phòng ban thành công.');
        } catch (\Exception $e) {
            Log::error('Update Permissions Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Lỗi cập nhật quyền hạn.');
        }
    }
}
