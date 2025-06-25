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

class UserController extends Controller
{
    protected $userRepo;
    protected $permissionRepo;
    public function __construct(UserRepositoryInterface $userRepo, UserPermissionRepositoryInterface $permissionRepo)
    {
        $this->userRepo = $userRepo;
        $this->permissionRepo = $permissionRepo;
    }

    public function index(FilterUserRequest $request)
    {
        $users = $this->userRepo->filterUsers($request->filters());
        return view('users.index', compact('users'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userRepo->updateUser($user, $request->validated());
        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $deleted = $this->userRepo->deleteUser($user);

        if (!$deleted) {
            return back()->with('error', 'You cannot delete this user.');
        }

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userRepo->createUser($request->validatedData());

        // Send password reset link
        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('admin.users.create')->with('success', 'User created and password reset link sent to their email.');
    }

    public function permissions()
    {
        $users = $this->permissionRepo->getStaffWithPermissions();
        $permissions = $this->permissionRepo->getAllPermissions();

        return view('users.permissions', compact('users', 'permissions'));
    }

    public function updatePermissions(UpdateUserPermissionsRequest $request)
    {
        $this->permissionRepo->updateUserPermissions(
            $request->user_id,
            $request->permissions?? []
        );

        return redirect()->back()->with('success', 'Permissions updated successfully.');
    }

}
