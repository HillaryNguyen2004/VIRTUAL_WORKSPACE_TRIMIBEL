<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\FilterUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\StoreUserRequest;


class UserController extends Controller
{
    public function index(FilterUserRequest $request)
{
    $filters = $request->filters();

    $query = User::with('roles');

    if (!empty($filters['search'])) {
        $query->where(function ($q) use ($filters) {
            $q->where('name', 'like', '%' . $filters['search'] . '%')
              ->orWhere('email', 'like', '%' . $filters['search'] . '%');
        });
    }

    if (!empty($filters['role'])) {
        $query->whereHas('roles', function ($q) use ($filters) {
            $q->where('name', $filters['role']);
        });
    }

    $users = $query->get();

    return view('users.index', compact('users'));
}


public function update(UpdateUserRequest $request, User $user)
{
    // Update name
    $user->name = $request->name;

    // Update team_leader_id if role is staff
    if ($request->role === 'staff') {
        User::where('team_leader_id', $user->id)->update(['team_leader_id' => null]);

        if ($request->filled('team_members')) {
            User::whereIn('id', $request->team_members)->update(['team_leader_id' => $user->id]);
        }
    } else {
        // Clear any team associations if not staff
        User::where('team_leader_id', $user->id)->update(['team_leader_id' => null]);
    }

    $user->save();

    // Update roles
    $user->syncRoles([$request->role]);

    return redirect()->route('users.index')->with('success', 'User updated successfully.');
}



public function destroy(User $user)
{
    if (auth()->id() === $user->id || $user->hasRole('admin')) {
        return back()->with('error', 'You cannot delete this user.');
    }

    $user->delete();

    return redirect()->route('users.index')->with('success', 'User deleted successfully.');
}


public function create()
{
    return view('users.create');
}

public function store(StoreUserRequest $request)
{
    $data = $request->validatedData();

    $tempPassword = Str::random(12);

    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($tempPassword),
    ]);

    $user->assignRole($data['roles']);

    Password::sendResetLink(['email' => $user->email]);

    return redirect()->route('admin.users.create')->with('success', 'User created and password reset link sent to their email.');
}
}