<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Student\StudentController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/students', [StudentController::class, 'index'])->middleware('permission:students.view')->name('students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->middleware('permission:students.create')->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->middleware('permission:students.create')->name('students.store');
    Route::view('/attendance', 'modules.coming-soon', ['title' => 'Attendance'])->name('attendance.index');
    Route::view('/finance', 'modules.coming-soon', ['title' => 'Finance & Accounts'])->name('finance.index');
    Route::view('/communication', 'modules.coming-soon', ['title' => 'WhatsApp Centre'])->name('communication.index');
});
