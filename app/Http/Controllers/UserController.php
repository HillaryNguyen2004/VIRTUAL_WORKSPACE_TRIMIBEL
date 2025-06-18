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
//     public function create()
//     {
//         $user = User::create([
//         'name' => $request->name,
//         'email' => $request->email,
//         'password' => Hash::make($randomPassword),
//     ]);

//     $user->assignRole($request->roles);
//         return view('users.create'); 
//     }


//     public function store(Request $request)
// {
//     $request->validate([
//         'name' => 'required|string|max:255',
//         'email' => 'required|email|unique:users,email',
//         'roles' => 'required|in:staff,user',
//     ]);

//     // Generate random password (not stored long term)
//     $tempPassword = Str::random(12);

//     $user = User::create([
//         'name' => $request->name,
//         'email' => $request->email,
//         'password' => Hash::make($tempPassword),
//         'roles' => $request->roles,
//     ]);

//     // Send password reset email
//     Password::sendResetLink(['email' => $user->email]);

//     return redirect()->back()->with('success', 'User created and password reset link sent!');
// }



// public function update(Request $request, User $user)
// {
//     $request->validate([
//         'name' => 'required|string|max:255',
//         'roles' => 'required|in:staff,user',
//         'team_members' => 'nullable|array',
//         'team_members.*' => 'exists:users,id',
//     ]);

//     $user->name = $request->name;
//     $user->save();

//     // Update Spatie role
//     if ($user->hasRole('staff') || $user->hasRole('user')) {
//         $user->removeRole($user->getRoleNames()->first());
//     }
//     $user->assignRole($request->roles);

//     // If user is staff, update team members
//     if ($request->roles === 'staff') {
//         // Clear old team members
//         User::where('team_leader_id', $user->id)->update(['team_leader_id' => null]);

//         // Assign new team members
//         if ($request->has('team_members')) {
//             User::whereIn('id', $request->team_members)->update(['team_leader_id' => $user->id]);
//         }
//     } else {
//         // If not staff, unassign them from leading anyone
//         User::where('team_leader_id', $user->id)->update(['team_leader_id' => null]);
//     }

//     return redirect()->route('users.index')->with('success', 'User updated successfully.');
// }

// public function destroy(User $user)
// {
//     // Optional: prevent deleting admin or yourself
//     if (auth()->id() === $user->id || $user->roles === 'admin') {
//         return back()->with('error', 'You cannot delete this user.');
//     }

//     $user->delete();

//     return redirect()->route('users.index')->with('success', 'User deleted successfully.');
// }



public function index(Request $request)
{
    $query = User::with('roles'); // eager load roles

    if ($request->filled('search')) {
        $query->where(function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->search . '%')
              ->orWhere('email', 'like', '%' . $request->search . '%');
        });
    }

    if ($request->filled('role')) {
        $query->whereHas('roles', function ($q) use ($request) {
            $q->where('name', $request->role);
        });
    }

    $users = $query->get();

    return view('users.index', compact('users'));
}



public function update(Request $request, User $user)
{
        $request->validate([
        'name' => 'required|string|max:255',
        'role' => 'required|in:user,staff,admin',
        'team_members' => 'array|nullable',
        'team_members.*' => 'nullable|exists:users,id',
    ]);

    // $user = User::findOrFail($id);

    // Update name
    $user->name = $request->name;

    // Update team_leader_id for users assigned to this staff
    if ($request->role === 'staff') {
        // Clear old assignments
        User::where('team_leader_id', $user->id)->update(['team_leader_id' => null]);

        // Reassign selected members
        if ($request->filled('team_members')) {
            User::whereIn('id', $request->team_members)->update(['team_leader_id' => $user->id]);
        }
    } else {
        // If changed to 'user', remove any current team members
        User::where('team_leader_id', $user->id)->update(['team_leader_id' => null]);
    }

    $user->save();

    // Remove current role(s) and assign new one
    $user->syncRoles([$request->role]);

    return redirect()->route('users.index')->with('success', 'User updated successfully.');
}


public function destroy(User $user)
{
    if (auth()->id() === $user->id || $user->roles === 'admin') {
        return back()->with('error', 'You cannot delete this user.');
    }

    $user->delete();

    return redirect()->route('users.index')->with('success', 'User deleted successfully.');
}


public function create()
{
    return view('users.create');
}

public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'roles' => 'required|in:user,staff',
    ]);

    // Generate random password
    $tempPassword = Str::random(12);

    // Create user
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($tempPassword),
    ]);

    // Assign role using Spatie
    $user->assignRole($request->roles);

    // Send password reset email
    Password::sendResetLink(['email' => $user->email]);

    return redirect()->route('admin.users.create')->with('success', 'User created and password reset link sent to their email.');
}
}