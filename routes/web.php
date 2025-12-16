<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\HolidayController;

Route::get('/', [LoanController::class, 'index'])->name('dashboard');
Route::get('/loans/create', [LoanController::class, 'create'])->name('loans.create');
Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
Route::get('/loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
Route::post('/payments', [LoanController::class, 'storePayment'])->name('payments.store');
Route::post('/loans/{loan}/settle', [LoanController::class, 'settle'])->name('loans.settle');

// Holidays management
Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
Route::post('/holidays', [HolidayController::class, 'store'])->name('holidays.store');
Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
