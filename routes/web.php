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

// Redirect root to login
// Route::get('/', function () {
//     return redirect()->route('login');
// });

Route::get('/', [AuthController::class, 'redirectToLogin']);


// Auth routes
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'loginPost'])->name('login.post');

Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'registerPost'])->name('register.post');

// Optional logout route (only if logout method exists in your controller)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected dashboard route
Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
    ->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed'])->name('verification.verify');

// Route::middleware(['auth'])->group(function () {
//     Route::get('/dashboard', function () {
//         return view('dashboard');
//     })->name('dashboard');
// });

// Route::middleware(['auth'])->group(function () {
//     Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
// });

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('user.dashboard')->middleware(['auth']);

Route::get('/admin/dashboard', function () {
    return view('admindashboard');
})->name('admin.dashboard')->middleware(['auth']);


Route::get('/tasks/new', [TaskController::class, 'create'])->name('tasks.create');
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');

Route::get('/staff/dashboard', function () {
    return view('staffdashboard');
})->name('staff.dashboard')->middleware(['auth']);

// Redirect after login
Route::get('/home', function () {
    // This triggers the middleware
})->middleware(['auth', 'role.redirect']);

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->name('dashboard');

Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::get('/profile', [ProfileController::class, 'showProfile'])->name('profile');
Route::get('/settings', [ProfileController::class, 'showSettings'])->name('settings');
// Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

Route::put('/settings/update-name', [SettingsController::class, 'updateName'])->name('settings.update.name');
Route::put('/settings/update-avatar', [SettingsController::class, 'updateAvatar'])->name('settings.update.avatar');

