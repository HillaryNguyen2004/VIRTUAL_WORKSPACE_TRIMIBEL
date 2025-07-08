<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use App\Http\Requests\FilterUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Repositories\UserRepositoryInterface;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateUserPermissionsRequest;
use App\Repositories\UserPermissionRepositoryInterface;
use App\Services\UserService;
use App\Services\UserImportService;
use App\Http\Requests\ImportUserRequest;

class UserController extends Controller
{
    protected $userRepo;
    protected $permissionRepo;
    protected $userService;
    protected $importService;
    public function __construct(UserService $userService,UserRepositoryInterface $userRepo, UserPermissionRepositoryInterface $permissionRepo, UserImportService $importService)
    {
        $this->userRepo = $userRepo;
        $this->permissionRepo = $permissionRepo;
        $this->userService = $userService;
        $this->importService = $importService;
    }

    public function index(FilterUserRequest $request)
    {
        $users = $this->userRepo->filterUsers($request->filters());
        return view('users.index', compact('users'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->updateUser($user, $request->validated());
        return redirect()->route('users.index')->with('success', __('messages.user_updated'));
    }

    public function destroy(User $user)
    {
        $deleted = $this->userService->deleteUser($user);

        if (!$deleted) {
            return back()->with('error',  __('messages.user_not_deleted'));
        }

        return redirect()->route('users.index')->with('success', __('messages.user_deleted'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validatedData());

        // Send password reset link
        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('admin.users.create')->with('success', __('messages.user_created'));
    }


    public function permissions()
    {
        $roles = \Spatie\Permission\Models\Role::with('permissions')->get();
        $permissions = $this->permissionRepo->getAllPermissions();

        return view('users.permissions', compact('roles', 'permissions'));
    }

    public function updatePermissions(UpdateUserPermissionsRequest $request)
    {
        $this->permissionRepo->updateRolePermissions(
            $request->role_name,
            $request->permissions ?? []
        );

        return redirect()->back()->with('success', __('messages.permissions_updated'));
    }


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
