<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeamController;
use App\Services\UserRoleRedirectService;
use App\Http\Controllers\DayOffController;

// Route::group(['middleware' => ['web', 'core']], function () {
//     include_once 'admin/user.php';
// });

include_once 'admin/user.php';
include_once 'staff/user.php';

// Redirect root to login
Route::get('/', [AuthController::class, 'redirectToLogin']);

// Auth routes
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'loginPost'])->name('login.post');
Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'registerPost'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Email Verification
Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed'])->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Dashboards
// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->name('user.dashboard')->middleware(['auth']);



Route::get('/dashboard', function (UserRoleRedirectService $redirectService) {
    return redirect()->to($redirectService->getDashboardRoute());
})->middleware(['auth'])->name('dashboard');

Route::get('/user/dashboard', [DashboardController::class, 'user'])->name('user.dashboard')->middleware('auth');

Route::get('/dayoff/request', [DayOffController::class, 'create'])->name('dayoff.request');
Route::post('/dayoff/request', [DayOffController::class, 'store'])->name('dayoff.request.store');


// Route::post('/notifications/{id}/read', function ($id) {
//     auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
//     return back();
// })->name('notifications.read')->middleware('auth');

Route::post('/notifications/clear', function () {
    cache()->forget('user_' . auth()->id() . '_dayoff_notice');
    return back();
})->name('notifications.clear')->middleware('auth');

Route::get('/notifications/unread', function () {
    return Auth::user()->unreadNotifications;
})->middleware('auth');



Route::middleware(['role:admin|staff'])->group(function () {

    // CREATE TASK
    Route::get('/management/tasks/create', [TaskController::class, 'create'])
        ->middleware('permission:task.create')
        ->name('tasks.create');

    Route::post('/management/tasks', [TaskController::class, 'store'])
        ->middleware('permission:task.create')
        ->name('tasks.store');

    // LIST + SHOW
    Route::get('/management/tasks', [TaskController::class, 'index'])
        ->name('tasks.index');

    Route::get('/management/tasks/{task}', [TaskController::class, 'show'])
        ->name('tasks.show');

    // EDIT TASK
    Route::get('/management/tasks/{task}/edit', [TaskController::class, 'edit'])
        ->middleware('permission:task.edit')
        ->name('tasks.edit');

    Route::put('/management/tasks/{task}', [TaskController::class, 'update'])
        ->middleware('permission:task.edit')
        ->name('tasks.update');

    // DELETE TASK
    Route::delete('/management/tasks/{task}', [TaskController::class, 'destroy'])
        ->middleware('permission:task.delete')
        ->name('tasks.destroy');
});



// Redirect after login
Route::get('/home', function () {
    // This triggers the middleware
})->middleware(['auth', 'role.redirect']);

// Password Reset
Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

// Google Login
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

// Profile and Settings
Route::get('/profile', [ProfileController::class, 'showProfile'])->name('profile');
Route::get('/settings', [ProfileController::class, 'showSettings'])->name('settings');
Route::put('/settings/update-name', [SettingsController::class, 'updateName'])->name('settings.update.name');
Route::put('/settings/update-avatar', [SettingsController::class, 'updateAvatar'])->name('settings.update.avatar');

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'vi'])) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }
    return redirect()->back();
})->name('lang.switch');
