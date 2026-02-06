<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CheckInService;

class ProfileController extends Controller
{
    protected $checkInService;

    public function __construct(CheckInService $checkInService)
    {
        $this->checkInService = $checkInService;
    }

    public function showProfile()
    {
        return view('profile');
    }

    public function showSettings()
    {
        return view('settings');
    }

    // public function registerFace(Request $request)
    // {
    //     $request->validate([
    //         'face_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    //     ]);

    //     $user = auth()->user();
    //     $result = $this->checkInService->registerFace($request->file('face_image'), $user->username);

    //     if ($result['status']) {
    //         return back()->with('success', $result['message']);
    //     } else {
    //         return back()->with('error', $result['message']);
    //     }
    // }


    public function showFaceRegister()
    {
        return view('face-register'); // face-register.blade.php
    }

    public function storeFaceRegister(Request $request)
    {
        $request->validate([
            'face_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = auth()->user();
        $result = $this->checkInService->registerFace($request->file('face_image'), $user->username);

        if ($result['status']) {
            return back()->with('success', $result['message']);
        } else {
            return back()->with('error', $result['message']);
        }
    }

    public function registerFace(Request $request)
    {
        $request->validate([
            'face_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = auth()->user();
        $result = $this->checkInService->registerFace($request->file('face_image'), $user->username);

        if ($result['status']) {
            return back()->with('success', $result['message']);
        } else {
            return back()->with('error', $result['message']);
        }
    }
}
