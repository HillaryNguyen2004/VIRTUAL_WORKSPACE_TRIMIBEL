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

class UserController extends Controller
{
    protected $userRepo;
    protected $permissionRepo;
    protected $userService;
    public function __construct(UserService $userService,UserRepositoryInterface $userRepo, UserPermissionRepositoryInterface $permissionRepo)
    {
        $this->userRepo = $userRepo;
        $this->permissionRepo = $permissionRepo;
        $this->userService = $userService;
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

    // public function permissions()
    // {
    //     $users = $this->permissionRepo->getStaffWithPermissions();
    //     $permissions = $this->permissionRepo->getAllPermissions();

    //     return view('users.permissions', compact('users', 'permissions'));
    // }

    // public function updatePermissions(UpdateUserPermissionsRequest $request)
    // {
    //     $this->permissionRepo->updateUserPermissions(
    //         $request->user_id,
    //         $request->permissions?? []
    //     );

    //     return redirect()->back()->with('success',  __('messages.permissions_updated'));
    // }


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
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="user_import_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            // Header row
            fputcsv($file, ['name', 'email', 'password', 'roles']);
            // Sample row
            fputcsv($file, ['John Doe', 'john@example.com', 'password123', 'user']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        $header = fgetcsv($handle); // Skip header

        while (($row = fgetcsv($handle)) !== false) {
            [$name, $email, $password, $roles] = $row;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue; // skip invalid email
            }

            // Skip if email already exists
            if (User::where('email', $email)->exists()) {
                continue;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password ?: 'password123'),
                'roles' => $roles ?: 'user',
            ]);

            // Optionally assign role with Spatie
            if (in_array($roles, ['admin', 'staff', 'user'])) {
                $user->assignRole($roles);
            }
        }

        fclose($handle);

        return redirect()->back()->with('success', __('messages.user_imported'));
    }

}
