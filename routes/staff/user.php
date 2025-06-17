<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;

// Staff dashboard
Route::get('/staff/dashboard', [TaskController::class, 'upcomingTasks'])
    ->name('staff.dashboard')
    ->middleware(['auth']);

// Staff tasks
Route::get('/staff/tasks', [TaskController::class, 'staffTasks'])
    ->name('tasks.staff.index');

// Team overview
Route::middleware(['auth'])->group(function () {
    Route::get('/staff/team', [TeamController::class, 'index'])
        ->name('team.overview');
});
