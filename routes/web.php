<?php

use App\Http\Controllers\Admin\ClinicController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\QueueController as AdminQueueController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\TelemedicineScheduleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MyQueueController;
use App\Http\Controllers\QueueRegistrationController;
use App\Http\Controllers\TelemedicineController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureStaff;
use App\Services\QueueManager;
use Illuminate\Support\Facades\Route;

/* ---------------------------- Pasien (tanpa akun) ---------------------------- */
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/telemedicine', [TelemedicineController::class, 'index'])->name('telemedicine');

Route::get('/antrian', [MyQueueController::class, 'index'])->name('queue.index');
Route::get('/antrian/status', [MyQueueController::class, 'status'])->name('queue.status');
Route::get('/antrian/daftar', [QueueRegistrationController::class, 'create'])->name('queue.register');
Route::post('/antrian', [QueueRegistrationController::class, 'store'])->middleware('throttle:10,1')->name('queue.store');
Route::post('/antrian/pulihkan', [MyQueueController::class, 'recover'])->middleware('throttle:5,1')->name('queue.recover');

/* ------------------------------ Admin / Petugas ------------------------------ */
Route::prefix('admin')->group(function () {
    // Nama route "login" dipakai middleware auth bawaan Laravel untuk redirect tamu.
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');
    Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth')->name('admin.logout');

    Route::middleware(['auth', EnsureStaff::class])->group(function () {
        Route::redirect('/', '/admin/antrian');

        Route::get('antrian', [AdminQueueController::class, 'index'])->name('admin.queue');
        Route::post('antrian/panggil-berikutnya', [AdminQueueController::class, 'next'])->name('admin.queue.next');
        Route::post('antrian/{ticket}/{action}', [AdminQueueController::class, 'action'])
            ->whereNumber('ticket')
            ->whereIn('action', QueueManager::ACTIONS)
            ->name('admin.queue.action');

        // Khusus admin
        Route::middleware(EnsureAdmin::class)->group(function () {
            Route::get('jadwal', [ScheduleController::class, 'index'])->name('admin.schedule');
            Route::put('jadwal', [ScheduleController::class, 'update'])->name('admin.schedule.update');
            Route::post('jadwal/libur', [ScheduleController::class, 'storeException'])->name('admin.schedule.exception.store');
            Route::delete('jadwal/libur/{exception}', [ScheduleController::class, 'destroyException'])
                ->whereNumber('exception')
                ->name('admin.schedule.exception.destroy');

            Route::get('klinik', [ClinicController::class, 'edit'])->name('admin.clinic');
            Route::put('klinik', [ClinicController::class, 'update'])->name('admin.clinic.update');

            Route::get('layanan', [ServiceController::class, 'index'])->name('admin.services');
            Route::post('layanan', [ServiceController::class, 'store'])->name('admin.services.store');
            Route::put('layanan/{service}', [ServiceController::class, 'update'])->whereNumber('service')->name('admin.services.update');
            Route::delete('layanan/{service}', [ServiceController::class, 'destroy'])->whereNumber('service')->name('admin.services.destroy');

            Route::get('telemedicine', [TelemedicineScheduleController::class, 'index'])->name('admin.telemedicine');
            Route::post('telemedicine', [TelemedicineScheduleController::class, 'store'])->name('admin.telemedicine.store');
            Route::delete('telemedicine/{schedule}', [TelemedicineScheduleController::class, 'destroy'])
                ->whereNumber('schedule')
                ->name('admin.telemedicine.destroy');
        });
    });
});