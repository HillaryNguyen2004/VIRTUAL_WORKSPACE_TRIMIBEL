<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;



// Route::get('/tasks/staff', [TaskController::class, 'staffTasks'])->name('tasks.staff');
Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/staff/dashboard', [TaskController::class, 'upcomingTasks'])->name('staff.dashboard');
    Route::get('/staff/tasks', [TaskController::class, 'staffTasks'])->name('tasks.staff.index');
    Route::get('/staff/team', [TeamController::class, 'index'])->name('team.overview');
    Route::post('/team/assign-task', [TeamController::class, 'assignTask'])->name('team.assignTask');
});


