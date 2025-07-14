<?php
use Illuminate\Support\Facades\Route;
use NguyenNguyen\CompanyHour\Controllers\CompanyHourController;


Route::middleware(['web', 'auth'])->prefix('admin/company-hours')->name('companyhour.')->group(function () {
    Route::get('/', [CompanyHourController::class, 'index'])->name('index');
    Route::get('/create', [CompanyHourController::class, 'create'])->name('create');
    Route::post('/', [CompanyHourController::class, 'store'])->name('store');
    // Route::get('/{companyhour}/edit', [CompanyHourController::class, 'edit'])->name('edit');
    // Route::put('/{companyhour}', [CompanyHourController::class, 'update'])->name('update');
    Route::get('/edit', [CompanyHourController::class, 'edit'])->name('edit');
    // Route::post('/companyhour/update', [CompanyHourController::class, 'update'])->name('update');
    Route::match(['post', 'put'], '/companyhour/update', [CompanyHourController::class, 'update'])->name('update');


    Route::delete('/{companyhour}', [CompanyHourController::class, 'destroy'])->name('destroy');
});
