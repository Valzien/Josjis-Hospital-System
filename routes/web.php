<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Doctor;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Patient;
use App\Http\Controllers\Pharmacy;
use App\Http\Controllers\Reception;
use App\Http\Controllers\ReportExportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'landing'])->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->middleware('throttle:6,1');

    Route::get('forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email')->middleware('throttle:3,1');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update')->middleware('throttle:3,1');
});

Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated (role dispatch)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('dashboard', [HomeController::class, 'dispatch'])->name('dashboard');
    Route::get('search', GlobalSearchController::class)->name('search.global');
});

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::resource('users', Admin\UserController::class);
        Route::put('users/{user}/status', [Admin\UserController::class, 'toggleStatus'])->name('users.status');
        Route::put('users/{user}/password', [Admin\UserController::class, 'resetPassword'])->name('users.password');

        Route::resource('doctors', Admin\DoctorController::class);
        Route::put('doctors/{doctor}/status', [Admin\DoctorController::class, 'toggleStatus'])->name('doctors.status');

        Route::resource('pharmacists', Admin\PharmacistController::class);
        Route::put('pharmacists/{pharmacist}/status', [Admin\PharmacistController::class, 'toggleStatus'])->name('pharmacists.status');

        Route::resource('receptionists', Admin\ReceptionistController::class);
        Route::put('receptionists/{receptionist}/status', [Admin\ReceptionistController::class, 'toggleStatus'])->name('receptionists.status');

        Route::resource('patients', Admin\PatientController::class);

        Route::resource('schedules', Admin\ScheduleController::class);

        Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [Admin\ReportController::class, 'show'])->name('reports.show');
        Route::get('reports/{report}/export/{format}', [ReportExportController::class, 'export'])->name('reports.export');

        Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('audit-logs/{auditLog}', [Admin\AuditLogController::class, 'show'])->name('audit-logs.show');

        Route::get('settings', [Admin\SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    });

/*
|--------------------------------------------------------------------------
| RESEPSIONIS
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:receptionist,admin'])
    ->prefix('reception')
    ->name('reception.')
    ->group(function () {
        Route::get('dashboard', [Reception\DashboardController::class, 'index'])->name('dashboard');

        Route::get('registration', [Reception\PatientController::class, 'create'])->name('registration.create');
        Route::post('registration', [Reception\PatientController::class, 'store'])->name('registration.store');
        Route::get('registration/{patient}/edit', [Reception\PatientController::class, 'edit'])->name('registration.edit');
        Route::put('registration/{patient}', [Reception\PatientController::class, 'update'])->name('registration.update');
        Route::get('registration/history', [Reception\PatientController::class, 'history'])->name('registration.history');

        Route::get('patients', [Reception\PatientController::class, 'index'])->name('patients.index');
        Route::get('patients/{patient}', [Reception\PatientController::class, 'show'])->name('patients.show');

        Route::get('queues', [Reception\QueueController::class, 'index'])->name('queues.index');
        Route::get('queues/board', [Reception\QueueController::class, 'board'])->name('queues.board');
        Route::get('queues/create', [Reception\QueueController::class, 'create'])->name('queues.create');
        Route::post('queues', [Reception\QueueController::class, 'store'])->name('queues.store');
        Route::get('queues/{queue}', [Reception\QueueController::class, 'show'])->name('queues.show');
        Route::post('queues/{queue}/call', [Reception\QueueController::class, 'call'])->name('queues.call');
        Route::post('queues/call-next', [Reception\QueueController::class, 'callNext'])->name('queues.call-next');
        Route::post('queues/{queue}/cancel', [Reception\QueueController::class, 'cancel'])->name('queues.cancel');
        Route::post('queues/{queue}/restore', [Reception\QueueController::class, 'restore'])->name('queues.restore');

        Route::get('schedules', [Reception\ScheduleController::class, 'index'])->name('schedules.index');
    });

/*
|--------------------------------------------------------------------------
| DOKTER
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:doctor,admin'])
    ->prefix('doctor')
    ->name('doctor.')
    ->group(function () {
        Route::get('dashboard', [Doctor\DashboardController::class, 'index'])->name('dashboard');

        Route::get('queues', [Doctor\QueueController::class, 'index'])->name('queues.index');
        Route::get('queues/{queue}', [Doctor\QueueController::class, 'show'])->name('queues.show');
        Route::post('queues/{queue}/start', [Doctor\QueueController::class, 'start'])->name('queues.start');
        Route::post('queues/{queue}/complete', [Doctor\QueueController::class, 'complete'])->name('queues.complete');

        Route::get('patients', [Doctor\PatientController::class, 'index'])->name('patients.index');
        Route::get('patients/{patient}', [Doctor\PatientController::class, 'show'])->name('patients.show');

        Route::get('examinations', [Doctor\ExaminationController::class, 'index'])->name('examinations.index');
        Route::get('examinations/{queue}/create', [Doctor\ExaminationController::class, 'create'])->name('examinations.create');
        Route::post('examinations/{queue}', [Doctor\ExaminationController::class, 'store'])->name('examinations.store');
        Route::get('examinations/{record}/show', [Doctor\ExaminationController::class, 'show'])->name('examinations.show');
        Route::get('examinations/{record}/print', [Doctor\ExaminationController::class, 'print'])->name('examinations.print');

        Route::get('medical-records', [Doctor\MedicalRecordController::class, 'index'])->name('medical-records.index');
        Route::get('medical-records/{record}', [Doctor\MedicalRecordController::class, 'show'])->name('medical-records.show');

        Route::get('prescriptions', [Doctor\PrescriptionController::class, 'index'])->name('prescriptions.index');
        Route::get('prescriptions/{prescription}', [Doctor\PrescriptionController::class, 'show'])->name('prescriptions.show');
        Route::get('prescriptions/{prescription}/print', [Doctor\PrescriptionController::class, 'print'])->name('prescriptions.print');

        Route::get('schedules', [Doctor\ScheduleController::class, 'index'])->name('schedules.index');
    });

/*
|--------------------------------------------------------------------------
| APOTEKER
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:pharmacist,admin'])
    ->prefix('pharmacy')
    ->name('pharmacy.')
    ->group(function () {
        Route::get('dashboard', [Pharmacy\DashboardController::class, 'index'])->name('dashboard');

        Route::get('prescriptions', [Pharmacy\PrescriptionController::class, 'index'])->name('prescriptions.index');
        Route::get('prescriptions/{prescription}', [Pharmacy\PrescriptionController::class, 'show'])->name('prescriptions.show');
        Route::post('prescriptions/{prescription}/process', [Pharmacy\PrescriptionController::class, 'process'])->name('prescriptions.process');
        Route::post('prescriptions/{prescription}/ready', [Pharmacy\PrescriptionController::class, 'ready'])->name('prescriptions.ready');
        Route::post('prescriptions/{prescription}/complete', [Pharmacy\PrescriptionController::class, 'complete'])->name('prescriptions.complete');
        Route::post('prescriptions/{prescription}/cancel', [Pharmacy\PrescriptionController::class, 'cancel'])->name('prescriptions.cancel');
        Route::get('prescriptions/{prescription}/print', [Pharmacy\PrescriptionController::class, 'print'])->name('prescriptions.print');

        Route::resource('medicines', Pharmacy\MedicineController::class);

        Route::get('stock', [Pharmacy\StockController::class, 'index'])->name('stock.index');
        Route::post('stock/{medicine}/in', [Pharmacy\StockController::class, 'stockIn'])->name('stock.in');
        Route::post('stock/{medicine}/out', [Pharmacy\StockController::class, 'stockOut'])->name('stock.out');
        Route::post('stock/{medicine}/adjust', [Pharmacy\StockController::class, 'adjust'])->name('stock.adjust');

        Route::get('transactions', [Pharmacy\TransactionController::class, 'index'])->name('transactions.index');
    });

/*
|--------------------------------------------------------------------------
| PASIEN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:patient'])
    ->prefix('patient')
    ->name('patient.')
    ->group(function () {
        Route::get('dashboard', [Patient\DashboardController::class, 'index'])->name('dashboard');

        Route::get('profile', [Patient\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [Patient\ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [Patient\ProfileController::class, 'updatePassword'])->name('profile.password');

        Route::get('doctors', [Patient\DoctorController::class, 'index'])->name('doctors.index');
        Route::get('doctors/{doctor}', [Patient\DoctorController::class, 'show'])->name('doctors.show');

        Route::get('schedules', [Patient\ScheduleController::class, 'index'])->name('schedules.index');

        Route::get('queue', [Patient\QueueController::class, 'index'])->name('queue.index');
        Route::get('queue/create', [Patient\QueueController::class, 'create'])->name('queue.create');
        Route::post('queue', [Patient\QueueController::class, 'store'])->name('queue.store');
        Route::get('queue/{queue}', [Patient\QueueController::class, 'show'])->name('queue.show');
        Route::post('queue/{queue}/cancel', [Patient\QueueController::class, 'cancel'])->name('queue.cancel');

        Route::get('history', [Patient\HistoryController::class, 'index'])->name('history.index');
        Route::get('history/{record}', [Patient\HistoryController::class, 'show'])->name('history.show');

        Route::get('prescriptions', [Patient\PrescriptionController::class, 'index'])->name('prescriptions.index');
        Route::get('prescriptions/{prescription}', [Patient\PrescriptionController::class, 'show'])->name('prescriptions.show');

        Route::get('medicines', [Patient\MedicineController::class, 'index'])->name('medicines.index');
    });
