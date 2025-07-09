<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckInController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public check-in (creates token)
Route::post('/check-in', [CheckInController::class, 'checkIn']);

// Authenticated routes (require token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/check-out', [CheckInController::class, 'checkOut']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});