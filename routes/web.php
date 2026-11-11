<?php

use App\Http\Controllers\Academic\AcademicController;
use App\Http\Controllers\Assessment\AssessmentController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Communication\CommunicationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\FinanceController;
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
    Route::get('/academics', [AcademicController::class, 'index'])->name('academics.index');
    Route::get('/assessments', [AssessmentController::class, 'index'])->middleware('permission:exams.manage')->name('assessments.index');
    Route::get('/assessments/{component}/entry', [AssessmentController::class, 'entry'])->middleware('permission:exams.manage')->name('assessments.entry');
    Route::put('/assessments/{component}/marks', [AssessmentController::class, 'store'])->middleware('permission:exams.manage')->name('assessments.marks.store');
    Route::post('/exams/{exam}/publish', [AssessmentController::class, 'publish'])->middleware('permission:exams.publish')->name('exams.publish');
    Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('permission:attendance.manage')->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->middleware('permission:attendance.manage')->name('attendance.store');
    Route::get('/finance', [FinanceController::class, 'index'])->middleware('permission:finance.manage')->name('finance.index');
    Route::post('/finance/invoices/{invoice}/collect', [FinanceController::class, 'collect'])->middleware('permission:finance.manage')->name('finance.collect');
    Route::get('/communication', [CommunicationController::class, 'index'])->middleware('permission:messages.manage')->name('communication.index');
    Route::post('/communication', [CommunicationController::class, 'store'])->middleware('permission:messages.manage')->name('communication.store');
    Route::post('/communication/{message}/approve', [CommunicationController::class, 'approve'])->middleware('permission:messages.approve')->name('communication.approve');
    Route::post('/communication/{message}/cancel', [CommunicationController::class, 'cancel'])->middleware('permission:messages.manage')->name('communication.cancel');
});
