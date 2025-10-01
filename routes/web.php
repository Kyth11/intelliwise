<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\AdminDashboardController;
use App\Http\Controllers\Auth\FacultyDashboardController;
use App\Http\Controllers\Auth\GuardianDashboardController;

Route::get('/', fn() => redirect()->route('login'));

// ====================
// Authentication
// ====================
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ====================
// Authenticated Routes
// ====================
Route::middleware(['auth'])->group(function () {

    /**
     * ====================
     * Admin Dashboard
     * ====================
     */
    Route::middleware(['auth','role:admin'])->prefix('admin')->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('admin.dashboard');

        // Students
        Route::get('/students', [StudentController::class, 'index'])->name('admin.students');
        Route::post('/students/store', [StudentController::class, 'store'])->name('students.store');
        Route::put('/students/{id}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{id}', [StudentController::class, 'destroy'])->name('students.destroy');

        // Faculties (account management, not schedule updates)
        Route::get('/faculties', [FacultyDashboardController::class, 'index'])->name('admin.faculties');
        Route::post('/faculties/store', [FacultyDashboardController::class, 'store'])->name('faculties.store');
        Route::put('/faculties/{id}', [FacultyDashboardController::class, 'update'])->name('faculties.update');
        Route::delete('/faculties/{id}', [FacultyDashboardController::class, 'destroy'])->name('faculties.destroy');

        // Guardians
        Route::get('/guardians', [GuardianDashboardController::class, 'index'])->name('admin.guardians');
        Route::post('/guardians/store', [GuardianDashboardController::class, 'store'])->name('guardians.store');
        Route::put('/guardians/{id}', [GuardianDashboardController::class, 'update'])->name('guardians.update');
        Route::delete('/guardians/{id}', [GuardianDashboardController::class, 'destroy'])->name('guardians.destroy');

        // Announcements
        Route::post('/announcements/store', [AdminDashboardController::class, 'storeAnnouncement'])->name('announcements.store');
        Route::put('/announcements/{id}', [AdminDashboardController::class, 'updateAnnouncement'])->name('announcements.update');
        Route::delete('/announcements/{id}', [AdminDashboardController::class, 'destroyAnnouncement'])->name('announcements.destroy');

        // Schedules (SINGLE SOURCE OF TRUTH — no duplicates)
        Route::get('/schedules', [AdminDashboardController::class, 'schedules'])->name('admin.schedules');
        Route::post('/schedules/store', [AdminDashboardController::class, 'storeSchedule'])->name('schedules.store');
        Route::put('/schedules/{id}', [AdminDashboardController::class, 'updateSchedule'])->name('schedules.update');
        Route::delete('/schedules/{id}', [AdminDashboardController::class, 'destroySchedule'])->name('schedules.destroy');

        // Tuition
        Route::post('/tuitions',        [AdminDashboardController::class, 'storeTuition'])->name('tuitions.store');
        Route::put('/tuitions/{id}',    [AdminDashboardController::class, 'updateTuition'])->name('tuitions.update');
        Route::delete('/tuitions/{id}', [AdminDashboardController::class, 'destroyTuition'])->name('tuitions.destroy');

        // (Optional) Backward-compatible alias so old Blade calls to route('tuition.destroy') still work:
        Route::delete('/tuition/{id}',  [AdminDashboardController::class, 'destroyTuition'])->name('tuition.destroy');

        // Settings
        Route::get('/settings', [AdminDashboardController::class, 'settings'])->name('admin.settings');

        // Settings actions: Admin accounts
        Route::post('/settings/admins', [AdminDashboardController::class, 'storeAdmin'])->name('admin.settings.admins.store');
        Route::delete('/settings/admins/{id}', [AdminDashboardController::class, 'destroyAdmin'])->name('admin.settings.admins.destroy');

        // Settings actions: School Year
        Route::post('/settings/school-year', [AdminDashboardController::class, 'storeSchoolYear'])->name('admin.settings.schoolyear.store');

        // Accounts overview
        Route::get('/accounts', [AdminDashboardController::class, 'accounts'])->name('admin.accounts');
    });

    /**
     * ====================
     * Faculty Dashboard (self-service)
     * ====================
     */
    Route::middleware(['role:faculty'])->group(function () {
        Route::get('/faculties/dashboard', [FacultyDashboardController::class, 'index'])->name('faculties.dashboard');
        Route::put('/faculties/{id}', [FacultyDashboardController::class, 'update'])->name('faculties.self.update');
    });

    /**
     * ====================
     * Guardian Dashboard (self-service)
     * ====================
     */
    Route::middleware(['role:guardian'])->group(function () {
        Route::get('/guardians/dashboard', [GuardianDashboardController::class, 'index'])->name('guardians.dashboard');
        Route::put('/guardians/{id}', [GuardianDashboardController::class, 'update'])->name('guardians.self.update');
    });
});
