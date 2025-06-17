<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admindashboard');
    })->name('admin.dashboard');

    Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users/store', [UserController::class, 'store'])->name('admin.users.store');

    // Admin user management
    Route::get('/management/users', [UserController::class, 'index'])->name('users.index');
});

Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
