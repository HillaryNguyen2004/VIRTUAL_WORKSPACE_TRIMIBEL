<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Validate\LoginRequest;
use App\Http\Validate\RegistrationRequest;

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

    public function loginPost(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            return redirect()->intended(route('dashboard'));
        }

        return redirect()->route('login')->with("error", __('auth.login_failed'));
    }

    public function registrationPost(RegistrationRequest $request)
    {
        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if (!$user) {
            return redirect()->route('registration')->with("error", __('auth.registration_failed'));
        }

        return redirect()->route('login')->with("success", __('auth.registration_success'));
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login')->with("success", __('auth.logout_success'));
    }
}