<?php
namespace App\Http\Controllers;
use App\Http\Requests\UpdateAvatarRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use App\Repositories\UserRepository;
class SettingsController extends Controller
{
    protected $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }
    public function updateName(UpdateProfileRequest $request)
    {
        $this->userRepo->updateName(auth()->user(), $request->first_name, $request->last_name);

        return back()->with('success', __('messages.profile_updated'));

    }

    public function updateAvatar(UpdateAvatarRequest $request)
    {
        $this->userRepo->updateAvatar(auth()->user(), $request->file('avatar'));

        return back()->with('success_avatar', __('messages.avatar_updated'));
    }
}