<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Add search/filter logic here
        $users = User::all();
        return view('users.index', compact('users'));
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
}