<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth routes
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'loginPost'])->name('login.post');

Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'registrationPost'])->name('registration.post');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ✅ Only logged-in users can access this
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
