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


// Tasks
// Route::resource('tasks', TaskController::class);
Route::get('/tasks/new', [TaskController::class, 'create'])->name('tasks.create');
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::get('/management/tasks', [TaskController::class, 'index'])->name('tasks.index');
Route::get('/management/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
Route::get('/management/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
// Route::put('/management/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::put('/management/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');

Route::delete('/management/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');



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
