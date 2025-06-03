<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthManager; 


// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth routes
Route::get('/login', [AuthManager::class, 'login'])->name('login');
Route::post('/login', [AuthManager::class, 'loginPost'])->name('login.post');

Route::get('/register', [AuthManager::class, 'register'])->name('register');
Route::post('/register', [AuthManager::class, 'registrationPost'])->name('registration.post');

Route::post('/logout', [AuthManager::class, 'logout'])->name('logout');

// ✅ Only logged-in users can access this
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
