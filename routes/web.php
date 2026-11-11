<?php

use App\Http\Controllers\Academic\AcademicController;
use App\Http\Controllers\Assessment\AssessmentController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Communication\CommunicationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Hr\HrController;
use App\Http\Controllers\Library\LibraryController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Timetable\TimetableController;
use App\Http\Controllers\Transport\TransportController;
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
    Route::get('/students/{student}', [StudentController::class, 'show'])->middleware('permission:students.view')->name('students.show');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->middleware('permission:students.update')->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->middleware('permission:students.update')->name('students.update');
    Route::post('/students/{student}/guardians', [StudentController::class, 'addGuardian'])->middleware('permission:students.update')->name('students.guardians.store');
    Route::post('/students/{student}/withdraw', [StudentController::class, 'withdraw'])->middleware('permission:students.archive')->name('students.withdraw');
    Route::get('/academics', [AcademicController::class, 'index'])->name('academics.index');
    Route::get('/timetable', [TimetableController::class, 'index'])->middleware('permission:timetable.manage')->name('timetable.index');
    Route::get('/timetable/create', [TimetableController::class, 'create'])->middleware('permission:timetable.manage')->name('timetable.create');
    Route::post('/timetable', [TimetableController::class, 'store'])->middleware('permission:timetable.manage')->name('timetable.store');
    Route::get('/assessments', [AssessmentController::class, 'index'])->middleware('permission:exams.manage')->name('assessments.index');
    Route::get('/assessments/{component}/entry', [AssessmentController::class, 'entry'])->middleware('permission:exams.manage')->name('assessments.entry');
    Route::put('/assessments/{component}/marks', [AssessmentController::class, 'store'])->middleware('permission:exams.manage')->name('assessments.marks.store');
    Route::post('/exams/{exam}/publish', [AssessmentController::class, 'publish'])->middleware('permission:exams.publish')->name('exams.publish');
    Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('permission:attendance.manage')->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->middleware('permission:attendance.manage')->name('attendance.store');
    Route::get('/transport', [TransportController::class, 'index'])->middleware('permission:transport.manage')->name('transport.index');
    Route::post('/transport/vehicles', [TransportController::class, 'storeVehicle'])->middleware('permission:transport.manage')->name('transport.vehicles.store');
    Route::post('/transport/assignments', [TransportController::class, 'assign'])->middleware('permission:transport.manage')->name('transport.assignments.store');
    Route::get('/library', [LibraryController::class, 'index'])->middleware('permission:library.manage')->name('library.index');
    Route::post('/library/books', [LibraryController::class, 'storeBook'])->middleware('permission:library.manage')->name('library.books.store');
    Route::post('/library/loans', [LibraryController::class, 'issue'])->middleware('permission:library.issue')->name('library.loans.issue');
    Route::post('/library/loans/{loan}/return', [LibraryController::class, 'returnBook'])->middleware('permission:library.issue')->name('library.loans.return');
    Route::get('/hr', [HrController::class, 'index'])->middleware('permission:hr.manage')->name('hr.index');
    Route::post('/hr/leaves/{leave}/review', [HrController::class, 'reviewLeave'])->middleware('permission:hr.approve')->name('hr.leaves.review');
    Route::post('/hr/payroll', [HrController::class, 'generatePayroll'])->middleware('permission:payroll.manage')->name('hr.payroll.generate');
    Route::post('/hr/payroll/{run}/approve', [HrController::class, 'approvePayroll'])->middleware('permission:payroll.approve')->name('hr.payroll.approve');
    Route::get('/finance', [FinanceController::class, 'index'])->middleware('permission:finance.manage')->name('finance.index');
    Route::post('/finance/invoices/{invoice}/collect', [FinanceController::class, 'collect'])->middleware('permission:finance.manage')->name('finance.collect');
    Route::get('/communication', [CommunicationController::class, 'index'])->middleware('permission:messages.manage')->name('communication.index');
    Route::post('/communication', [CommunicationController::class, 'store'])->middleware('permission:messages.manage')->name('communication.store');
    Route::post('/communication/{message}/approve', [CommunicationController::class, 'approve'])->middleware('permission:messages.approve')->name('communication.approve');
    Route::post('/communication/{message}/cancel', [CommunicationController::class, 'cancel'])->middleware('permission:messages.manage')->name('communication.cancel');
});
