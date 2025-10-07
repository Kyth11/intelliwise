<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\AdminDashboardController;
use App\Http\Controllers\Auth\FacultyDashboardController;
use App\Http\Controllers\Auth\GuardianDashboardController;

use App\Http\Controllers\StudentController;
use App\Http\Controllers\GradesController;

Route::get('/', fn () => redirect()->route('login'));

// ====================
// Authentication
// ====================
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
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
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('admin.dashboard');

        // Finances
        Route::get('/finances', [AdminDashboardController::class, 'finances'])->name('admin.finances');

        // Students
        Route::get('/students',         [StudentController::class, 'index'])->name('admin.students');
        Route::get('/students/enroll',  [StudentController::class, 'create'])->name('students.create');
        Route::post('/students/store',  [StudentController::class, 'store'])->name('students.store');
        Route::put('/students/{id}',    [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{id}', [StudentController::class, 'destroy'])->name('students.destroy');

        // Faculties (account management)
        Route::get('/faculties',         [FacultyDashboardController::class, 'index'])->name('admin.faculties');
        Route::post('/faculties/store',  [FacultyDashboardController::class, 'store'])->name('faculties.store');
        Route::put('/faculties/{id}',    [FacultyDashboardController::class, 'update'])->name('faculties.update');
        Route::delete('/faculties/{id}', [FacultyDashboardController::class, 'destroy'])->name('faculties.destroy');

        // Guardians (account management)
        Route::get('/guardians',         [GuardianDashboardController::class, 'index'])->name('admin.guardians');
        Route::post('/guardians/store',  [GuardianDashboardController::class, 'store'])->name('guardians.store');
        Route::put('/guardians/{id}',    [GuardianDashboardController::class, 'update'])->name('guardians.update');
        Route::delete('/guardians/{id}', [GuardianDashboardController::class, 'destroy'])->name('guardians.destroy');

        // Announcements (Admin)
        Route::post('/announcements/store', [AdminDashboardController::class, 'storeAnnouncement'])->name('announcements.store');
        Route::put('/announcements/{id}',   [AdminDashboardController::class, 'updateAnnouncement'])->name('announcements.update');
        Route::delete('/announcements/{id}',[AdminDashboardController::class, 'destroyAnnouncement'])->name('announcements.destroy');

        // Schedules (admin management)
        Route::get('/schedules',        [AdminDashboardController::class, 'schedules'])->name('admin.schedules');
        Route::post('/schedules/store', [AdminDashboardController::class, 'storeSchedule'])->name('schedules.store');
        Route::put('/schedules/{id}',   [AdminDashboardController::class, 'updateSchedule'])->name('schedules.update');
        Route::delete('/schedules/{id}',[AdminDashboardController::class, 'destroySchedule'])->name('schedules.destroy');

        // Tuition + Optional fees
        Route::post('/tuitions',        [AdminDashboardController::class, 'storeTuition'])->name('tuitions.store');
        Route::put('/tuitions/{id}',    [AdminDashboardController::class, 'updateTuition'])->name('tuitions.update');
        Route::delete('/tuitions/{id}', [AdminDashboardController::class, 'destroyTuition'])->name('tuitions.destroy');
        Route::delete('/tuition/{id}',  [AdminDashboardController::class, 'destroyTuition'])->name('tuition.destroy');

        Route::post('/optional-fees',        [AdminDashboardController::class, 'storeOptionalFee'])->name('optionalfees.store');
        Route::put('/optional-fees/{id}',    [AdminDashboardController::class, 'updateOptionalFee'])->name('optionalfees.update');
        Route::delete('/optional-fees/{id}', [AdminDashboardController::class, 'destroyOptionalFee'])->name('optionalfees.destroy');

        // Settings + Accounts
        Route::get('/settings', [AdminDashboardController::class, 'settings'])->name('admin.settings');
        Route::post('/settings/admins',        [AdminDashboardController::class, 'storeAdmin'])->name('admin.settings.admins.store');
        Route::delete('/settings/admins/{id}', [AdminDashboardController::class, 'destroyAdmin'])->name('admin.settings.admins.destroy');
        Route::post('/settings/school-year',   [AdminDashboardController::class, 'storeSchoolYear'])->name('admin.settings.schoolyear.store');

        Route::get('/accounts', [AdminDashboardController::class, 'accounts'])->name('admin.accounts');

        // Grades
        Route::get('/grades', [GradesController::class, 'index'])->name('admin.grades');
    });

    /**
     * ====================
     * Faculty Panel (self-service)
     * ====================
     */
    Route::middleware(['role:faculty'])
        ->prefix('faculty')
        ->name('faculty.')
        ->group(function () {
            Route::get('/', [FacultyDashboardController::class, 'index'])->name('dashboard');

            // Students list (scoped)
            Route::get('/students', [FacultyDashboardController::class, 'students'])->name('students');

            // Announcements (Faculty-scoped CRUD)
            Route::post('/announcements',        [FacultyDashboardController::class, 'storeAnnouncement'])->name('announcements.store');
            Route::put('/announcements/{id}',    [FacultyDashboardController::class, 'updateAnnouncement'])->name('announcements.update');
            Route::delete('/announcements/{id}', [FacultyDashboardController::class, 'destroyAnnouncement'])->name('announcements.destroy');

            // Faculty Enrollment page uses faculty layout
            Route::get('/students/enroll',  [FacultyDashboardController::class, 'enrollForm'])->name('students.create');
            Route::post('/students/store',  [StudentController::class, 'store'])->name('students.store');

            // Other faculty pages
            Route::get('/schedule', [FacultyDashboardController::class, 'schedule'])->name('schedule');
            Route::view('/settings', 'auth.facultydashboard.setting')->name('settings');

            // ✅ Self profile update (no id)
            Route::put('/profile', [FacultyDashboardController::class, 'updateSelf'])->name('profile.update');

            // (Optional) Keep the by-id route with a different name for admin tools or future use
            Route::put('/profile/{id}', [FacultyDashboardController::class, 'update'])->name('profile.update.byid');
        });

    /**
     * ====================
     * Guardian Panel (self-service)
     * ====================
     */
    Route::middleware(['role:guardian'])
        ->prefix('guardians')
        ->name('guardians.')
        ->group(function () {
            Route::get('/dashboard', [GuardianDashboardController::class, 'index'])->name('dashboard');

            Route::view('/children', 'auth.guardiandashboard.children')->name('children');
            Route::view('/reports',  'auth.guardiandashboard.reports')->name('reports');
            Route::view('/settings', 'auth.guardiandashboard.settings')->name('settings');

            // Self update
            Route::put('/{id}', [GuardianDashboardController::class, 'update'])->name('self.update');
        });
});
