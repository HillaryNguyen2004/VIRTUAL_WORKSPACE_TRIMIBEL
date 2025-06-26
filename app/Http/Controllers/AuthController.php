<?php
namespace App\Http\Controllers;
use App\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegistrationRequest;
use App\Services\LoginService;

class AuthController extends Controller
{
    protected $userRepository;
    protected $loginService;
    public function __construct(UserRepository $userRepository, LoginService $loginService)
    {
        $this->userRepository = $userRepository;
        $this->loginService = $loginService;
    }

    public function redirectToLogin() {
    return redirect()->route('login');
    }

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
        $user = $this->userRepository->createFromRequest($request);
        $user->assignRole('user');
        $user->sendEmailVerificationNotification();
        Auth::login($user);

        return redirect()->route('verification.notice');
    }
    // call app/Services/LoginService to handle login logic
    // POST /login → handles form submission
    public function loginPost(LoginRequest $request)
    {
    
    try {
        $user = $this->loginService->login(
            $request->only('email', 'password'),
            $request->filled('remember')
        );

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('staff')) {
            return redirect()->route('staff.dashboard');
        } else {
            return redirect()->route('user.dashboard');
        }


    } catch (\Illuminate\Validation\ValidationException $e) {
        return back()->withErrors($e->errors());
    }
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login')->with("success", __('auth.logout_success'));
    }
}