<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    
    // public function index(Request $request)
    // {
    //     // Add search/filter logic here
    //     $users = User::all();
    //     return view('users.index', compact('users'));
    // }
    public function create()
    {
        $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($randomPassword),
    ]);

    $user->assignRole($request->roles);
        return view('users.create'); 
    }


    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'roles' => 'required|in:staff,user',
    ]);

    // Generate random password (not stored long term)
    $tempPassword = Str::random(12);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($tempPassword),
        'roles' => $request->roles,
    ]);

    // Send password reset email
    Password::sendResetLink(['email' => $user->email]);

    return redirect()->back()->with('success', 'User created and password reset link sent!');
}

public function index(Request $request)
{
    $query = User::query();

    if ($request->filled('search')) {
        $query->where(function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->search . '%')
              ->orWhere('email', 'like', '%' . $request->search . '%');
        });
    }

    if ($request->filled('role')) {
        $query->where('roles', $request->role);
    }

    if ($request->filled('status')) {
        if ($request->status == 'active') {
            $query->where('email_verified_at', '!=', null);
        } elseif ($request->status == 'inactive') {
            $query->whereNull('email_verified_at');
        }
    }

    $users = $query->get();

    return view('users.index', compact('users'));
}

public function update(Request $request, User $user)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'roles' => 'required|in:staff,user',
        'team_members' => 'nullable|array',
        'team_members.*' => 'exists:users,id',
    ]);

    $user->name = $request->name;
    $user->roles = $request->roles;
    $user->save();

    // If user is staff, update team members
    if ($user->roles === 'staff') {
        // Clear old team members
        User::where('team_leader_id', $user->id)->update(['team_leader_id' => null]);

        // Assign new team members
        if ($request->has('team_members')) {
            User::whereIn('id', $request->team_members)->update(['team_leader_id' => $user->id]);
        }
    } else {
        // If not staff, unassign them from leading anyone
        User::where('team_leader_id', $user->id)->update(['team_leader_id' => null]);
    }

    return redirect()->route('users.index')->with('success', 'User updated successfully.');
}

public function destroy(User $user)
{
    // Optional: prevent deleting admin or yourself
    if (auth()->id() === $user->id || $user->roles === 'admin') {
        return back()->with('error', 'You cannot delete this user.');
    }

    $user->delete();

    return redirect()->route('users.index')->with('success', 'User deleted successfully.');
}
}