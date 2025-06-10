<?php
namespace App\Http\Controllers\Auth;
use App\Repositories\UserRepository;
use App\Http\Controllers\Controller;
use Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


// This controller handles Google authentication
// It redirects users to Google for authentication and handles the callback
// after authentication to log them in or create a new user if they don't exist, handle this in app/Repositories/UserRepository.php
class GoogleController extends Controller
{
    protected $userRepository;
    public function __construct(UserRepository $userRepository)
{
    $this->userRepository = $userRepository;
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

        return redirect()->route('dashboard');
    }
}