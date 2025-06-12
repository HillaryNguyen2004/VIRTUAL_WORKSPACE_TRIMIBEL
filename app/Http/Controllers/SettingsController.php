<?php
namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function updateName(UpdateProfileRequest $request)
    {
        $user = auth()->user();
        $user->name = $request->first_name . ' ' . $request->last_name;
        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updateAvatar(Request $request)
{
    $request->validate([
        'avatar' => 'required|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    $user = auth()->user();
    $file = $request->file('avatar');

    // Sanitize email to use as a file name (replace @ and . to avoid filesystem issues)
    $safeEmail = str_replace(['@', '.'], '_', $user->email);
    $extension = $file->getClientOriginalExtension();
    $filename = $safeEmail . '.' . $extension;

    // Save file to public/img/user_avatar/
    $file->move(public_path('img/user_avatar/'), $filename);

    return redirect()->back()->with('success_avatar', 'Profile picture updated successfully.');
}
}