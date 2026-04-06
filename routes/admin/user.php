<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\UserExportController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\Admin\LSTMDashboardController;


Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware('admin_or_permission:admin.dashboard.view')
        ->name('admin.dashboard');

    // ===== LSTM PRODUCTIVITY ANALYTICS MODULE =====
    // Dashboard view route
    Route::get('/admin/lstm-dashboard', [LSTMDashboardController::class, 'index'])
        ->middleware('admin_or_permission:admin.dashboard.view')
        ->name('admin.lstm.dashboard');

    // LSTM API endpoints - all under /api/lstm prefix for consistency
    Route::prefix('api/lstm')->group(function () {
        Route::get('stats', [LSTMDashboardController::class, 'getStats'])
            ->middleware('admin_or_permission:admin.dashboard.view')
            ->name('api.lstm.stats');

        Route::get('trends', [LSTMDashboardController::class, 'getTrends'])
            ->middleware('admin_or_permission:admin.dashboard.view')
            ->name('api.lstm.trends');

        Route::get('distribution', [LSTMDashboardController::class, 'getDistribution'])
            ->middleware('admin_or_permission:admin.dashboard.view')
            ->name('api.lstm.distribution');

        Route::get('employee-predictions', [LSTMDashboardController::class, 'getEmployeePredictions'])
            ->middleware('admin_or_permission:admin.dashboard.view')
            ->name('api.lstm.predictions');

        Route::get('employee-history/{id}', [LSTMDashboardController::class, 'getEmployeeHistory'])
            ->middleware('admin_or_permission:admin.dashboard.view')
            ->name('api.lstm.employee.history');

        Route::post('refresh-predictions', [LSTMDashboardController::class, 'refreshPredictions'])
            ->middleware('admin_or_permission:admin.dashboard.edit')
            ->name('api.lstm.refresh');

        Route::post('alerts/productivity-concern', [LSTMDashboardController::class, 'sendProductivityAlert'])
            ->middleware('admin_or_permission:admin.dashboard.edit')
            ->name('api.alerts.productivity');
    });

    // ===== USERS MODULE =====
    Route::get('/management/users', [UserController::class, 'index'])
        ->middleware('admin_or_permission:admin.users.view')
        ->name('admin.users.index');

    Route::get('/admin/users/create', [UserController::class, 'create'])
        ->middleware('admin_or_permission:admin.users.create')
        ->name('admin.users.create');

    Route::post('/admin/users/store', [UserController::class, 'store'])
        ->middleware('admin_or_permission:admin.users.create')
        ->name('admin.users.store');

    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('admin_or_permission:admin.users.edit')
        ->name('users.update');

    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('admin_or_permission:admin.users.delete')
        ->name('users.destroy');

    Route::get('/export-users-excel', [UserExportController::class, 'exportExcel'])
        ->middleware('admin_or_permission:admin.users.view');

    Route::get('/admin/users/import', [UserController::class, 'showImportForm'])
        ->middleware('admin_or_permission:admin.users.create')
        ->name('admin.users.import.form');

    Route::post('/admin/users/import', [UserController::class, 'import'])
        ->middleware('admin_or_permission:admin.users.create')
        ->name('admin.users.import');

    Route::get('/admin/users/import/template', [UserController::class, 'downloadTemplate'])
        ->middleware('admin_or_permission:admin.users.create')
        ->name('admin.users.import.template');

    // ===== ROLES & PERMISSIONS MODULE =====
    Route::get('/admin/permissions', [UserController::class, 'permissions'])
        ->middleware('admin_or_permission:admin.roles.view')
        ->name('admin.permissions');

    Route::post('/admin/permissions', [UserController::class, 'updatePermissions'])
        ->middleware('admin_or_permission:admin.roles.edit')
        ->name('admin.permissions.update');

    // routes/web.php
    Route::get('/admin/users/permissions', [UserController::class, 'permissions'])
        ->middleware('admin_or_permission:admin.roles.view')
        ->name('admin.users.permissions');

    Route::post('/admin/departments/{department}/roles/{role}/permissions', [UserController::class, 'updateDepartmentRolePermissions'])
        ->middleware('admin_or_permission:admin.roles.edit')
        ->name('admin.departments.roles.permissions.update');

    // Subadmin Management Routes
    Route::get('/admin/subadmins', [UserController::class, 'subadminIndex'])
        ->middleware('admin_or_permission:admin.roles.view')
        ->name('admin.subadmins.index');

    Route::post('/admin/subadmins/{user}/make', [UserController::class, 'makeSubadmin'])
        ->middleware('admin_or_permission:admin.roles.edit')
        ->name('admin.subadmins.make');

    Route::get('/admin/subadmins/{user}/permissions', [UserController::class, 'editSubadminPermissions'])
        ->middleware('admin_or_permission:admin.roles.view')
        ->name('admin.subadmins.permissions.edit');

    Route::post('/admin/subadmins/{user}/permissions', [UserController::class, 'updateSubadminPermissions'])
        ->middleware('permission:admin.roles.edit')
        ->name('admin.subadmins.permissions.update');

    // ===== CAMPAIGNS MODULE =====
    Route::resource('campaigns', CampaignController::class)
        ->middleware('admin_or_permission:admin.campaigns.view');

    Route::post('/campaigns/{campaign}/send-now', [CampaignController::class, 'sendNow'])
        ->middleware('admin_or_permission:admin.campaigns.edit')
        ->name('campaigns.sendNow');

    Route::put('/campaigns/{campaign}/reset', [CampaignController::class, 'reset'])
        ->middleware('admin_or_permission:admin.campaigns.edit')
        ->name('campaigns.reset');

    // ===== EMAIL TEMPLATES MODULE =====
    Route::resource('email-templates', EmailTemplateController::class)
        ->middleware('admin_or_permission:admin.email_templates.view');

    // ===== ACTIVITY LOGS MODULE =====
    Route::get('/admin/activity-logs', [AdminDashboardController::class, 'viewAllLogs'])
        ->middleware('admin_or_permission:admin.activity_logs.view')
        ->name('admin.activity.logs');

    // ===== ATTENDANCE MODULE =====
    Route::get('/check-ins', [CheckInController::class, 'index'])
        ->middleware('admin_or_permission:admin.attendance.view')
        ->name('users.checkin_index');

    Route::get('/admin/check-ins/export', [CheckInController::class, 'export'])
        ->middleware('admin_or_permission:admin.attendance.view')
        ->name('checkins.export');

    // ===== COMPANY HOURS MODULE =====
    // Add routes for company_hours if they exist in your controller
    // Route::resource('company-hours', CompanyHoursController::class)
    //     ->middleware('permission:admin.company_hours.view');

    // ===== PROJECTS MODULE =====
    // Add routes for projects if they exist in your controller
    // Route::resource('projects', ProjectController::class)
    //     ->middleware('permission:admin.projects.view');

    // ===== SUBSTAFF MODULE =====
    // Add routes for staff.substaff if they exist in your controller
    // Route::resource('substaffs', SubstaffController::class)
    //     ->middleware('permission:staff.substaff.view');

    // ===== DEPARTMENTS (implicit module, using admin.users permissions) =====
    Route::get('/admin/departments', [DepartmentController::class, 'index'])
        ->middleware('admin_or_permission:admin.users.view')
        ->name('admin.departments.index');

    Route::post('/admin/departments', [DepartmentController::class, 'store'])
        ->middleware('admin_or_permission:admin.users.create')
        ->name('admin.departments.store');

    Route::put('/admin/departments/{department}', [DepartmentController::class, 'update'])
        ->middleware('admin_or_permission:admin.users.edit')
        ->name('admin.departments.update');

    Route::delete('/admin/departments/{department}', [DepartmentController::class, 'destroy'])
        ->middleware('admin_or_permission:admin.users.delete')
        ->name('admin.departments.destroy');

    Route::post('/admin/departments/{department}/assfcamign', [DepartmentController::class, 'assignStaff'])
        ->middleware('admin_or_permission:admin.users.edit')
        ->name('admin.departments.assign');

    Route::delete('/admin/departments/{department}/remove/{user}', [DepartmentController::class, 'removeStaff'])
        ->middleware('permission:admin.users.edit')
        ->name('admin.departments.remove');

    Route::get('/admin/departments/{department}/permissions', [DepartmentController::class, 'editPermissions'])
        ->middleware('admin_or_permission:admin.roles.view')
        ->name('admin.departments.permissions.edit');

    Route::post('/admin/departments/{department}/permissions', [DepartmentController::class, 'updatePermissions'])
        ->middleware('permission:admin.roles.edit')
        ->name('admin.departments.permissions.update');
});

// Route::middleware(['auth'])->group(function () {

//     Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
//         ->middleware('admin_or_permission:admin.dashboard.view')
//         ->name('admin.dashboard');

//     Route::get('/management/users', [UserController::class, 'index'])
//         ->middleware('admin_or_permission:admin.users.view')
//         ->name('users.index');

//     Route::get('/admin/users/create', [UserController::class, 'create'])
//         ->middleware('admin_or_permission:admin.users.create')
//         ->name('admin.users.create');

//     Route::post('/admin/users/store', [UserController::class, 'store'])
//         ->middleware('admin_or_permission:admin.users.create')
//         ->name('admin.users.store');

//     Route::put('/users/{user}', [UserController::class, 'update'])
//         ->middleware('admin_or_permission:admin.users.edit')
//         ->name('users.update');

//     Route::delete('/users/{user}', [UserController::class, 'destroy'])
//         ->middleware('admin_or_permission:admin.users.delete')
//         ->name('users.destroy');

//     Route::resource('campaigns', CampaignController::class)
//         ->middleware('admin_or_permission:admin.campaigns.view');

//     Route::post('/campaigns/{campaign}/send-now', [CampaignController::class, 'sendNow'])
//         ->middleware('admin_or_permission:admin.campaigns.edit')
//         ->name('campaigns.sendNow');

//     Route::put('/campaigns/{campaign}/reset', [CampaignController::class, 'reset'])
//         ->middleware('admin_or_permission:admin.campaigns.edit')
//         ->name('campaigns.reset');

//     Route::resource('email-templates', EmailTemplateController::class)
//         ->middleware('admin_or_permission:admin.email_templates.view');

//     Route::get('/admin/activity-logs', [AdminDashboardController::class, 'viewAllLogs'])
//         ->middleware('admin_or_permission:admin.activity_logs.view')
//         ->name('admin.activity.logs');

//     Route::get('/check-ins', [CheckInController::class, 'index'])
//         ->middleware('admin_or_permission:admin.attendance.view')
//         ->name('users.checkin_index');

//     Route::get('/admin/check-ins/export', [CheckInController::class, 'export'])
//         ->middleware('admin_or_permission:admin.attendance.view')
//         ->name('checkins.export');

//     // Departments
//     Route::get('/admin/departments', [DepartmentController::class, 'index'])
//         ->middleware('admin_or_permission:admin.users.view')
//         ->name('admin.departments.index');

//     Route::post('/admin/departments', [DepartmentController::class, 'store'])
//         ->middleware('admin_or_permission:admin.users.create')
//         ->name('admin.departments.store');

//     Route::put('/admin/departments/{department}', [DepartmentController::class, 'update'])
//         ->middleware('admin_or_permission:admin.users.edit')
//         ->name('admin.departments.update');

//     Route::delete('/admin/departments/{department}', [DepartmentController::class, 'destroy'])
//         ->middleware('admin_or_permission:admin.users.delete')
//         ->name('admin.departments.destroy');

//     Route::get('/admin/departments/{department}/permissions', [DepartmentController::class, 'editPermissions'])
//         ->middleware('admin_or_permission:admin.roles.view')
//         ->name('admin.departments.permissions.edit');

//     Route::post('/admin/departments/{department}/permissions', [DepartmentController::class, 'updatePermissions'])
//         ->middleware('admin_or_permission:admin.roles.edit')
//         ->name('admin.departments.permissions.update');
// });


