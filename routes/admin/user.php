<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\UserExportController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\DepartmentController;


Route::middleware(['auth', 'role:admin'])->group(function () {
    // Route::get('/admin/dashboard', function () {
    //     return view('admindashboard');
    // })->name('admin.dashboard');

    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users/store', [UserController::class, 'store'])->name('admin.users.store');

    Route::get('/management/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/admin/departments', [DepartmentController::class, 'index'])->name('admin.departments.index');
    Route::post('/admin/departments', [DepartmentController::class, 'store'])->name('admin.departments.store');
    Route::put('/admin/departments/{department}', [DepartmentController::class, 'update'])->name('admin.departments.update');
    Route::delete('/admin/departments/{department}', [DepartmentController::class, 'destroy'])->name('admin.departments.destroy');
    Route::post('/admin/departments/{department}/assign', [DepartmentController::class, 'assignStaff'])->name('admin.departments.assign');
    Route::delete('/admin/departments/{department}/remove/{user}', [DepartmentController::class, 'removeStaff'])->name('admin.departments.remove');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('role:admin');
    Route::get('/admin/permissions', [UserController::class, 'permissions'])->name('admin.permissions');
    Route::post('/admin/permissions', [UserController::class, 'updatePermissions'])->name('admin.permissions.update');
    Route::get('/admin/activity-logs', [App\Http\Controllers\AdminDashboardController::class, 'viewAllLogs'])->name('admin.activity.logs');
    Route::resource('campaigns', CampaignController::class)->middleware(['auth', 'role:admin']);
    Route::resource('email-templates', EmailTemplateController::class)->middleware(['auth', 'role:admin']);
    Route::post('/campaigns/{campaign}/send-now', [CampaignController::class, 'sendNow'])->name('campaigns.sendNow');
    Route::get('/export-users-excel', [UserExportController::class, 'exportExcel']);
    Route::get('/admin/users/import', [UserController::class, 'showImportForm'])->name('admin.users.import.form');
    Route::post('/admin/users/import', [UserController::class, 'import'])->name('admin.users.import');
    Route::get('/admin/users/import/template', [UserController::class, 'downloadTemplate'])->name('admin.users.import.template');
    Route::put('/campaigns/{campaign}/reset', [CampaignController::class, 'reset'])->name('campaigns.reset');
    Route::get('/check-ins', [CheckInController::class, 'index'])->name('users.checkin_index');
    Route::get('/admin/check-ins/export', [CheckInController::class, 'export'])->name('checkins.export');


    // Route::get('/tasks/new', [TaskController::class, 'create'])->name('tasks.create');
    // Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    // Route::get('/management/tasks', [TaskController::class, 'index'])->name('tasks.index');
    // Route::get('/management/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    // Route::get('/management/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    // // Route::put('/management/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    // Route::put('/management/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');

    // Route::delete('/management/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
});
