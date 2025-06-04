<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegistrationRequest;

class AuthController extends Controller
{
    public function login()
    {
        return view('login');
    }

    public function register()
    {
        return view('register');
    }

    // POST /register → handles form submission
    public function registerPost(RegistrationRequest $request)
    {
        $user = User::create([
        'name' => $request->first_name . ' ' . $request->last_name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
    ]);

        $user->sendEmailVerificationNotification();
        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    public function loginPost(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return back()->withErrors(['email' => __('auth.no_user_found')]);
        }

        // if (!$user->hasVerifiedEmail()) {
        //     $user->sendEmailVerificationNotification();
        //     return back()->withErrors(['email' => 'Please verify your email first. A new link has been sent.']);
        // }

        if (Auth::attempt($credentials)) {
            Auth::login($user);
            return redirect()->intended(route('dashboard'));
        }

        // Auth::login($user); // log them in
        return back()->withErrors(['password' => __('auth.incorrect_password')]);
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login')->with("success", __('auth.logout_success'));
    }
}