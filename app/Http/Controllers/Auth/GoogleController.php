<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Services\UserRoleRedirectService;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    protected $userRepository;
    protected $redirectService;

    public function __construct(UserRepository $userRepository, UserRoleRedirectService $redirectService)
    {
        $this->userRepository = $userRepository;
        $this->redirectService = $redirectService;
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = $this->userRepository->findOrCreateFromGoogle($googleUser);

        Auth::login($user, true);

        return redirect()->to($this->redirectService->getDashboardRoute());
    }
}
