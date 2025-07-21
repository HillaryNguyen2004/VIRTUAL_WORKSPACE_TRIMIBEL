<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\DayOffController;



// Route::get('/tasks/staff', [TaskController::class, 'staffTasks'])->name('tasks.staff');
Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/staff/dashboard', [TaskController::class, 'upcomingTasks'])->name('staff.dashboard');
    Route::get('/staff/tasks', [TaskController::class, 'staffTasks'])->name('tasks.staff.index');
    Route::get('/staff/team', [TeamController::class, 'index'])->name('team.overview');
    Route::post('/team/assign-task', [TeamController::class, 'assignTask'])->name('team.assignTask');
    Route::get('/dayoff/staff/pending', [DayOffController::class, 'staffPendingRequests'])->name('dayoff.staff.pending');
    Route::post('/dayoff/{id}/approve', [DayOffController::class, 'approve'])->name('dayoff.approve');
    Route::post('/dayoff/{id}/reject', [DayOffController::class, 'reject'])->name('dayoff.reject');
});


