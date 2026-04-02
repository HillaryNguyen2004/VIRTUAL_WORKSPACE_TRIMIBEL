<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use App\Services\UserRoleRedirectService;
use App\Http\Controllers\DayOffController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\TeamProgressController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\FaceRegisterController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\WBOController;
use App\Http\Controllers\OnlineDocumentController;
use Illuminate\Http\Request;

// Route::group(['middleware' => ['web', 'core']], function () {
//     include_once 'admin/user.php';
// });

require_once __DIR__ . '/admin/user.php';
require_once __DIR__ . '/staff/user.php';

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

Route::middleware(['auth'])->prefix('online-docs')->name('online-docs.')->group(function () {
    Route::get('/', [OnlineDocumentController::class, 'landing'])->name('home');
    Route::post('/folders', [OnlineDocumentController::class, 'createFolder'])->name('folders.store');
    Route::put('/folders/{folder}', [OnlineDocumentController::class, 'renameFolder'])->name('folders.update');
    Route::delete('/folders/{folder}', [OnlineDocumentController::class, 'deleteFolder'])->name('folders.delete');
    Route::post('/files', [OnlineDocumentController::class, 'uploadPersonalFile'])->name('files.store');
    Route::put('/files/{file}', [OnlineDocumentController::class, 'renameFile'])->name('files.update');
    Route::delete('/files/{file}', [OnlineDocumentController::class, 'deleteFile'])->name('files.delete');
    Route::get('/files/{file}/download', [OnlineDocumentController::class, 'downloadPersonalFile'])->name('files.download');
    Route::get('/files/{file}/preview', [OnlineDocumentController::class, 'previewPersonalFile'])->name('files.preview');
    Route::get('/files/{file}/open', [OnlineDocumentController::class, 'openPersonalFile'])->name('files.open');
    Route::post('/links/{document}', [OnlineDocumentController::class, 'addDocumentLink'])->name('links.store');
    Route::put('/links/{link}', [OnlineDocumentController::class, 'renameDocumentLink'])->name('links.update');
    Route::delete('/links/{link}', [OnlineDocumentController::class, 'deleteDocumentLink'])->name('links.delete');
    Route::post('/storage/move', [OnlineDocumentController::class, 'moveStorageItem'])->name('storage.move');
    Route::post('/storage/bulk-move', [OnlineDocumentController::class, 'bulkMoveStorageItems'])->name('storage.bulk-move');
    Route::post('/storage/bulk-delete', [OnlineDocumentController::class, 'bulkDeleteStorageItems'])->name('storage.bulk-delete');
    Route::get('/docs', [OnlineDocumentController::class, 'docsIndex'])->name('docs');
    Route::post('/docs', [OnlineDocumentController::class, 'store'])->name('docs.store');
    Route::post('/excel', [OnlineDocumentController::class, 'createExcel'])->name('excel.create');
    Route::get('/excel', [OnlineDocumentController::class, 'excelIndex'])->name('excel');
    Route::post('/powerpoint', [OnlineDocumentController::class, 'createPowerpoint'])->name('powerpoint.create');
    Route::get('/powerpoint', [OnlineDocumentController::class, 'powerpointIndex'])->name('powerpoint');
    Route::get('/docs/{document}', [OnlineDocumentController::class, 'show'])->name('docs.show');
    Route::put('/docs/{document}', [OnlineDocumentController::class, 'update'])->name('docs.update');
    Route::put('/docs/{document}/rename', [OnlineDocumentController::class, 'rename'])->name('docs.rename');
    Route::delete('/docs/{document}', [OnlineDocumentController::class, 'destroy'])->name('docs.delete');
    Route::get('/docs/{document}/xlsx', [OnlineDocumentController::class, 'downloadXlsx'])->name('docs.xlsx');
    Route::post('/docs/{document}/xlsx', [OnlineDocumentController::class, 'saveXlsx'])->name('docs.xlsx.save');
    Route::post('/docs/{document}/import', [OnlineDocumentController::class, 'importDocx'])->name('docs.import');
    Route::post('/docs/{document}/import-xlsx', [OnlineDocumentController::class, 'importXlsx'])->name('docs.import.xlsx');
    Route::post('/docs/{document}/import-pptx', [OnlineDocumentController::class, 'importPptx'])->name('docs.import.pptx');
    Route::get('/docs/{document}/export', [OnlineDocumentController::class, 'exportDocx'])->name('docs.export');
    Route::post('/docs/{document}/share', [OnlineDocumentController::class, 'share'])->name('docs.share');
    Route::put('/docs/{document}/share', [OnlineDocumentController::class, 'updateShare'])->name('docs.share.update');
    Route::delete('/docs/{document}/share', [OnlineDocumentController::class, 'removeShare'])->name('docs.share.remove');
    Route::get('/docs/{document}/presence', [OnlineDocumentController::class, 'presence'])->name('docs.presence');
    Route::post('/docs/{document}/presence', [OnlineDocumentController::class, 'touchPresence'])->name('docs.presence.touch');
});

Route::prefix('onlyoffice')->name('onlyoffice.')->group(function () {
    Route::get('/files/{document}', [OnlineDocumentController::class, 'onlyofficeFile'])
        ->middleware('signed')
        ->name('files');
    Route::post('/callback/{document}', [OnlineDocumentController::class, 'onlyofficeCallback'])
        ->middleware('signed')
        ->name('callback');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/ai', function () {
        return view('ai.upload');
    })->name('ai.upload');

    Route::post('/ai', function (Request $request) {
        $files = $request->file('data_files', []);
        $fileCount = is_array($files) ? count($files) : 0;
        $workspaceName = trim((string) $request->input('workspace_name_upload'));
        $target = $workspaceName !== '' ? $workspaceName : __('ai.workspace_unnamed');

        return back()->with('status', __('ai.upload_received', [
            'count' => $fileCount,
            'workspace' => $target,
        ]));
    })->name('ai.upload.store');

    Route::post('/ai/workspaces', function (Request $request) {
        $name = trim((string) $request->input('workspace_name'));
        $safeName = $name !== '' ? $name : __('ai.workspace_new');

        return back()->with('workspace_status', __('ai.workspace_created', [
            'workspace' => $safeName,
        ]));
    })->name('ai.workspaces.store');
});

Route::get('/dayoff/request', [DayOffController::class, 'create'])->name('dayoff.request');
Route::post('/dayoff/request', [DayOffController::class, 'store'])->name('dayoff.request.store');
Route::post('/dayoff/halfday-preview', [DayOffController::class, 'halfDayPreview'])
    ->name('dayoff.halfday.preview');



// Route::post('/notifications/{id}/read', function ($id) {
//     auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
//     return back();
// })->name('notifications.read')->middleware('auth');

Route::post('/notifications/clear', function () {
    cache()->forget('user_' . auth()->id() . '_dayoff_notice');
    return back();
})->name('notifications.clear')->middleware('auth');

Route::get('/notifications/unread', function () {
    return Auth::user()->unreadNotifications;
})->middleware('auth');


Route::post('/notifications/read/{id}', [App\Http\Controllers\NotificationController::class, 'markAsRead'])
    ->name('notifications.read');

Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])
    ->name('notifications.readAll');

// Test route for real-time notifications (remove in production)
Route::get('/test-notification', function () {
    $user = Auth::user();
    $user->notify(new \App\Notifications\TaskAssignedNotification(
        999,
        'Test Task for Real-time Notifications',
        'System Test'
    ));
    return response()->json(['message' => 'Test notification sent']);
})->middleware('auth')->name('test.notification');

// Test route for task notification specifically
Route::get('/test-task-notification', function () {
    $user = Auth::user();
    $user->notify(new \App\Notifications\TaskAssignedNotification(
        998,
        'Test Task Assignment Notification',
        Auth::user()->name
    ));
    return response()->json(['message' => 'Task notification sent', 'user_id' => $user->id]);
})->middleware('auth')->name('test.task.notification');




// Route::middleware(['role:admin|staff'])->group(function () {

//     // CREATE TASK
//     Route::get('/management/tasks/create', [TaskController::class, 'create'])
//         ->middleware('permission:task.create')
//         ->name('tasks.create');

//     Route::post('/management/tasks', [TaskController::class, 'store'])
//         ->middleware('permission:task.create')
//         ->name('tasks.store');

//     // LIST + SHOW
//     Route::get('/management/tasks', [TaskController::class, 'index'])
//         ->name('tasks.index');

//     Route::get('/management/tasks/{task}', [TaskController::class, 'show'])
//         ->name('tasks.show');

//     Route::get('/admin/back-to-project-tasks', function () {
//         return redirect()->route('projects.index', ['tab' => 'tasks']);
//     })->name('admin.back.projects.tasks');

//     // EDIT TASK
//     Route::get('/management/tasks/{task}/edit', [TaskController::class, 'edit'])
//         ->middleware('permission:task.edit')
//         ->name('tasks.edit');

//     Route::put('/management/tasks/{task}', [TaskController::class, 'update'])
//         ->middleware('permission:task.edit')
//         ->name('tasks.update');

//     // DELETE TASK
//     Route::delete('/management/tasks/{task}', [TaskController::class, 'destroy'])
//         ->middleware('permission:task.delete')
//         ->name('tasks.destroy');
// });

// CREATE TASK
Route::get('/management/tasks/create', [TaskController::class, 'create'])
    ->middleware('admin_or_permission:task.create')
    ->name('tasks.create');

Route::post('/management/tasks', [TaskController::class, 'store'])
    ->middleware('admin_or_permission:task.create')
    ->name('tasks.store');

// LIST + SHOW
Route::get('/management/tasks', [TaskController::class, 'index'])
    ->name('tasks.index');

Route::get('/management/tasks/{task}', [TaskController::class, 'show'])
    ->name('tasks.show');

Route::get('/admin/back-to-project-tasks', function () {
    return redirect()->route('projects.index', ['tab' => 'tasks']);
})->name('admin.back.projects.tasks');

// EDIT TASK
Route::get('/management/tasks/{task}/edit', [TaskController::class, 'edit'])
    ->middleware('admin_or_permission:task.edit')
    ->name('tasks.edit');

Route::put('/management/tasks/{task}', [TaskController::class, 'update'])
    ->middleware('admin_or_permission:task.edit')
    ->name('tasks.update');

// DELETE TASK
Route::delete('/management/tasks/{task}', [TaskController::class, 'destroy'])
    ->middleware('admin_or_permission:task.delete')
    ->name('tasks.destroy');

// DETAIL TASK
Route::get('/management/tasks/{task}/details', [TaskController::class, 'details'])
    ->name('tasks.details');

Route::post('/management/tasks/{task}/mark-read', [TaskController::class, 'markRead'])
    ->name('tasks.markRead');

Route::get('/back/tasks/{task}', function (\App\Models\Task $task) {
    return redirect()->route('tasks.details', $task->id);
})->name('back.tasks.details');

Route::get('/projects', [ProjectController::class, 'index'])
    ->name('projects.index');

Route::get('/projects/create', [ProjectController::class, 'create'])
    ->middleware('admin_or_permission:admin.projects.create')
    ->name('projects.create');

Route::post('/projects/store', [ProjectController::class, 'store'])
    ->name('projects.store');

Route::get('/projects/{id}/edit', [ProjectController::class, 'edit'])
    ->middleware('admin_or_permission:admin.projects.edit')
    ->name('projects.edit');

Route::put('/projects/{id}', [ProjectController::class, 'update'])
    ->middleware('admin_or_permission:admin.projects.edit')
    ->name('projects.update');

Route::delete('/projects/{id}', [ProjectController::class, 'destroy'])
    ->middleware('admin_or_permission:admin.projects.delete')
    ->name('projects.destroy');

Route::get('/projects/{id}/details', [ProjectController::class, 'details'])
    ->name('projects.details');

Route::get('/projects/{id}/kanban', [ProjectController::class, 'kanban'])
    ->name('projects.kanban');


Route::put('/phases/{phase}', [App\Http\Controllers\PhaseController::class, 'update'])->name('phases.update');
Route::post('/projects/{project}/phases', [App\Http\Controllers\PhaseController::class, 'store'])->name('phases.store');
Route::delete('/phases/{phase}', [App\Http\Controllers\PhaseController::class, 'destroy'])->name('phases.destroy');

Route::get('/back/projects/{project}', function (\App\Models\Project $project) {
    return redirect()->route('projects.details', ['id' => $project->id]);
})->name('back.projects.details');

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
Route::post('/profile/register-face', [ProfileController::class, 'registerFace'])->name('profile.register.face');
Route::put('/settings/update-name', [SettingsController::class, 'updateName'])->name('settings.update.name');
Route::put('/settings/update-avatar', [SettingsController::class, 'updateAvatar'])->name('settings.update.avatar');
Route::get('/face/register', [ProfileController::class, 'showFaceRegister'])
    ->name('face.register');
// Route::post('/face/register', [ProfileController::class, 'storeFaceRegister'])
//     ->name('face.register.store');
Route::post('/face/register', [FaceRegisterController::class, 'store'])
    ->name('face.register.store')
    ->middleware('auth');

Route::post('/profile/register-face', [ProfileController::class, 'registerFace'])
    ->name('profile.register.face')
    ->middleware('auth');

// routes/web.php
Route::get('/profile/check-face-status', function () {
    $user = auth()->user();
    return response()->json([
        'face_registered' => !empty($user->face_image_path)
    ]);
})->middleware('auth');

Route::post('/face/verify', [CheckInController::class, 'verify'])
    ->middleware('auth');


Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'vi'])) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }
    return redirect()->back();
})->name('lang.switch');

// Chat routes - accessible to all authenticated users
Route::middleware(['auth'])->group(function () {
    Route::get('/chat', function () {
        return view('chat.realtime');
    })->name('chat.index');


    // Test route for user search
    Route::get('/chat/test-users', function () {
        $users = \App\Models\User::where('id', '!=', auth()->id())
            ->select(['id', 'name', 'email'])
            ->limit(5)
            ->get();
        return response()->json(['users' => $users]);
    })->name('chat.test.users');

    // Old chat routes (keep for backward compatibility)
    //Route::get('/chat/old', [App\Http\Controllers\ChatController::class, 'index'])->name('chat.old.index');
    //Route::get('/chat/conversation/{conversation}', [App\Http\Controllers\ChatController::class, 'show'])->name('chat.conversation');
    //Route::post('/chat/message', [App\Http\Controllers\ChatController::class, 'store'])->name('chat.message.store');
    //Route::post('/chat/create', [App\Http\Controllers\ChatController::class, 'createConversation'])->name('chat.create');
});


// Task status update route
// Route::post('/tasks/{id}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
// In your routes file (web.php)
Route::post('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');

// Video Chat
// Route::get('meeting', function () {
//     return view('video-chat.index');
// })->name('meet');

Route::get('/meeting', [MeetingController::class, 'index'])->name('meeting');

Route::get('/meetings/history', [MeetingController::class, 'history'])->name('meetings.history');

Route::get('/meetings/{meetingHistoryId}/details', [MeetingController::class, 'details'])
    ->middleware(['auth'])
    ->name('meetings.details');

Route::post('/meetings/history/leave', [MeetingController::class, 'recordLeave'])
    ->middleware(['auth'])
    ->name('meetings.history.leave');

Route::post("/createMeeting", [MeetingController::class, 'createMeeting'])->name("createMeeting");
Route::post("/validateMeeting", [MeetingController::class, 'validateMeeting'])->name("validateMeeting");

// Route::get("/meeting/{meetingId}", function($meetingId) {

//     $METERED_DOMAIN = env('METERED_DOMAIN');
//     return view('video-chat.meeting', [
//         'METERED_DOMAIN' => $METERED_DOMAIN,
//         'MEETING_ID' => $meetingId
//     ]);
// });

// Route 1: The Lobby Page
Route::get('/meeting/{meetingId}', [MeetingController::class, 'showLobby'])
    ->name('meeting.lobby');

// Route 2: The Meeting Room Page
Route::get('/meeting/{meetingId}/room', [MeetingController::class, 'showMeetingRoom'])
    ->name('meeting.room');

Route::post('/meeting/{meetingId}/chat', [MeetingController::class, 'sendChatMessage'])
    ->middleware(['auth'])
    ->name('meeting.chat.send');

// Whiteboard (WBO) Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/whiteboard', [WBOController::class, 'index'])->name('wbo.index');
    Route::post('/whiteboard/create', [WBOController::class, 'create'])->name('wbo.create');
    Route::post('/whiteboard/open', [WBOController::class, 'open'])->name('wbo.open');
    Route::get('/whiteboard/{boardId}', [WBOController::class, 'board'])->name('wbo.board');
});

Route::get('/team-progress', [TeamProgressController::class, 'index'])->name('team-progress');
Route::get('/user-tasks/{userId}', [TaskController::class, 'getUserTasks'])->name('user.tasks');

Route::middleware(['auth'])->group(function () {
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/events', [CalendarController::class, 'getEvents'])->name('calendar.events');
    Route::post('/calendar/store', [CalendarController::class, 'store'])->name('calendar.store');
    Route::patch('/calendar/update', [CalendarController::class, 'updateDate'])->name('calendar.update'); // For Drag & Drop
    Route::put('/calendar/update-details', [CalendarController::class, 'updateDetails'])->name('calendar.update-details'); // For Edit Modal
    Route::delete('/calendar/destroy', [CalendarController::class, 'destroy'])->name('calendar.destroy'); // For Delete Button

    Route::get('/calendar/google/connect', [CalendarController::class, 'connectGoogle'])->name('calendar.google.connect');
    Route::get('/calendar/google/callback', [CalendarController::class, 'googleCallback']);
});


// Face check-in routes

// Update your existing check-in routes to use the face check-in
Route::middleware(['auth'])->group(function () {
    Route::get('/checkin/face/{type}', [CheckInController::class, 'showFacePage'])
        ->whereIn('type', ['checkin', 'checkout'])
        ->name('checkin.face.page');
});

Route::post(
    '/checkin/face/process',
    [CheckInController::class, 'faceProcess']
)->middleware('auth')->name('checkin.face.process');

Route::post(
    '/checkin/manual/process',
    [CheckInController::class, 'manualProcess']
)->middleware('auth')->name('checkin.manual.process');

Route::get('/subadmin/dashboard', [AdminDashboardController::class, 'index'])
    ->middleware(['auth', 'permission:admin.dashboard.view'])
    ->name('subadmin.dashboard');

Route::resource('holidays', HolidayController::class);

Route::get('/substaff/dashboard', [DashboardController::class, 'substaffDashboard'])
    // ->middleware(['auth', 'permission:staff.dashboard.view'])
    ->name('substaff.dashboard');

Route::middleware('auth')->get('/whiteboard/{board}', function (Request $request, string $board) {
    $wboUrl = 'http://127.0.0.1:5001/boards/' . rawurlencode($board);
    return view('whiteboard.show', compact('wboUrl', 'board'));
})->name('whiteboard.show');