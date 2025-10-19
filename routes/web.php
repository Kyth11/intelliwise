<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\AdminDashboardController;
use App\Http\Controllers\Auth\FacultyDashboardController;
use App\Http\Controllers\Auth\GuardianDashboardController;
use App\Http\Controllers\Auth\PaymentsController;

use App\Http\Controllers\StudentController;
use App\Http\Controllers\GradesController;
use App\Http\Controllers\FacultyGradesController;
use App\Http\Controllers\Admin\GradeQuarterController;
use App\Http\Controllers\EnrollmentReportController;

Route::get('/', fn() => redirect()->route('login'));

// ====================
// Authentication
// ====================
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ====================
// Admin (auth + role:admin)
// ====================
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('dashboard');

        // Finances
        Route::get('/finances', [AdminDashboardController::class, 'finances'])->name('finances');

        // Students (admin-managed)
        Route::get('/students', [StudentController::class, 'index'])->name('students');
        Route::get('/students/enroll', [StudentController::class, 'create'])->name('students.create');
        Route::post('/students/store', [StudentController::class, 'store'])->name('students.store');
        Route::put('/students/{id}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{id}', [StudentController::class, 'destroy'])->name('students.destroy');

        // Faculties (account management)
        Route::get('/faculties', [FacultyDashboardController::class, 'index'])->name('faculties');
        Route::post('/faculties/store', [FacultyDashboardController::class, 'store'])->name('faculties.store');
        Route::put('/faculties/{id}', [FacultyDashboardController::class, 'update'])->name('faculties.update');
        Route::delete('/faculties/{id}', [FacultyDashboardController::class, 'destroy'])->name('faculties.destroy');

        // Guardians (account management)
        Route::get('/guardians', [GuardianDashboardController::class, 'index'])->name('guardians');
        Route::post('/guardians/store', [GuardianDashboardController::class, 'store'])->name('guardians.store');
        Route::put('/guardians/{id}', [GuardianDashboardController::class, 'update'])->name('guardians.update');
        Route::delete('/guardians/{id}', [GuardianDashboardController::class, 'destroy'])->name('guardians.destroy');

        // Announcements (Admin)
        Route::post('/announcements/store', [AdminDashboardController::class, 'storeAnnouncement'])->name('announcements.store');
        Route::put('/announcements/{id}', [AdminDashboardController::class, 'updateAnnouncement'])->name('announcements.update');
        Route::delete('/announcements/{id}', [AdminDashboardController::class, 'destroyAnnouncement'])->name('announcements.destroy');

        // Schedules (admin management)
        Route::get('/schedules', [AdminDashboardController::class, 'schedules'])->name('schedules');
        Route::post('/schedules/store', [AdminDashboardController::class, 'storeSchedule'])->name('schedules.store');
        Route::put('/schedules/{id}', [AdminDashboardController::class, 'updateSchedule'])->name('schedules.update');
        Route::delete('/schedules/{id}', [AdminDashboardController::class, 'destroySchedule'])->name('schedules.destroy');

        // Tuition + Optional fees
        Route::post('/tuitions', [AdminDashboardController::class, 'storeTuition'])->name('tuitions.store');
        Route::put('/tuitions/{id}', [AdminDashboardController::class, 'updateTuition'])->name('tuitions.update');
        Route::delete('/tuitions/{id}', [AdminDashboardController::class, 'destroyTuition'])->name('tuitions.destroy');
        Route::delete('/tuition/{id}', [AdminDashboardController::class, 'destroyTuition'])->name('tuition.destroy');

        Route::post('/optional-fees', [AdminDashboardController::class, 'storeOptionalFee'])->name('optionalfees.store');
        Route::put('/optional-fees/{id}', [AdminDashboardController::class, 'updateOptionalFee'])->name('optionalfees.update');
        Route::delete('/optional-fees/{id}', [AdminDashboardController::class, 'destroyOptionalFee'])->name('optionalfees.destroy');

        // Settings + Accounts
        Route::put('/settings/system', [AdminDashboardController::class, 'updateSystemSettings'])->name('settings.system.update');
        Route::get('/settings', [AdminDashboardController::class, 'settings'])->name('settings');
        Route::post('/settings/admins', [AdminDashboardController::class, 'storeAdmin'])->name('settings.admins.store');
        Route::delete('/settings/admins/{id}', [AdminDashboardController::class, 'destroyAdmin'])->name('settings.admins.destroy');
        Route::post('/settings/school-year', [AdminDashboardController::class, 'storeSchoolYear'])->name('settings.schoolyear.store');

        // Accounts page
        Route::get('/accounts', [AdminDashboardController::class, 'accounts'])->name('accounts');

        // Grades (Admin view)
        Route::get('/grades', [GradesController::class, 'index'])->name('grades');

        // NEW (GLOBAL toggles; matches QuarterLock)
        Route::post('/grades/quarters/save', [GradeQuarterController::class, 'save'])->name('grades.quarters.save');

        // Payments (admin only)
        Route::post('/payments/store', [PaymentsController::class, 'store'])->name('payments.store');

        // Reports (Admin)
        Route::get('/reports/enrollments', [EnrollmentReportController::class, 'index'])->name('reports.enrollments');
        Route::get('/reports/enrollments/print', [EnrollmentReportController::class, 'print'])->name('reports.enrollments.print');
        Route::get('/reports/enrollments/students', [EnrollmentReportController::class, 'students'])->name('reports.enrollments.students');
        Route::get('/reports/enrollments/export', [EnrollmentReportController::class, 'export'])->name('reports.enrollments.export');

        // Subjects (Admin)
        Route::post('/subjects', [AdminDashboardController::class, 'storeSubject'])->name('subjects.store');
        Route::put('/subjects/{id}', [AdminDashboardController::class, 'updateSubject'])->name('subjects.update');
        Route::delete('/subjects/{id}', [AdminDashboardController::class, 'destroySubject'])->name('subjects.destroy');

        // (Optional) AJAX endpoint in your comment
        Route::get('/grades/report', [GradesController::class, 'reportAjax'])->name('grades.report');
    });

// ====================
// Faculty Panel (auth + role:faculty)
// ====================
Route::middleware(['auth', 'role:faculty'])
    ->prefix('faculty')
    ->name('faculty.')
    ->group(function () {
        // Keep "/" usable; send to dashboard
        Route::get('/', fn() => redirect()->route('faculty.dashboard'));

        // Dashboard (uses existing FacultyDashboardController)
        Route::get('/dashboard', [FacultyDashboardController::class, 'index'])->name('dashboard');

        // Students list (read-only list page)
        Route::get('/students', [FacultyDashboardController::class, 'students'])->name('students');

        // Enrollment flow (guarded by your FacultyEnrollmentEnabled middleware alias "enrollment.open")
        Route::get('/students/create', [StudentController::class, 'create'])
            ->middleware('enrollment.open')
            ->name('students.create');

        Route::post('/students', [StudentController::class, 'store'])
            ->middleware('enrollment.open')
            ->name('students.store');

        // Announcements (Faculty-scoped CRUD)
        Route::post('/announcements', [FacultyDashboardController::class, 'storeAnnouncement'])->name('announcements.store');
        Route::put('/announcements/{id}', [FacultyDashboardController::class, 'updateAnnouncement'])->name('announcements.update');
        Route::delete('/announcements/{id}', [FacultyDashboardController::class, 'destroyAnnouncement'])->name('announcements.destroy');

        // Schedule
        Route::get('/schedule', [FacultyDashboardController::class, 'schedule'])->name('schedule');

        // Simple settings view
        Route::view('/settings', 'auth.facultydashboard.setting')->name('settings');

        // Profile (self) + by id
        Route::put('/profile', [FacultyDashboardController::class, 'updateSelf'])->name('profile.update');
        Route::put('/profile/{id}', [FacultyDashboardController::class, 'update'])->name('profile.update.byid');

        // Grades (Faculty)
        Route::get('/grades', [FacultyGradesController::class, 'index'])->name('grades.index');
        Route::post('/grades', [FacultyGradesController::class, 'store'])->name('grades.store');
    });

// ====================
// Guardian Panel (auth + role:guardian)
// ====================
Route::middleware(['auth', 'role:guardian'])
    ->prefix('guardians')
    ->name('guardians.')
    ->group(function () {
        Route::get('/dashboard', [GuardianDashboardController::class, 'index'])->name('dashboard');
        Route::view('/children', 'auth.guardiandashboard.children')->name('children');

        // Use controller so the blade gets data
        Route::get('/reports', [GuardianDashboardController::class, 'reports'])->name('reports');

        Route::view('/settings', 'auth.guardiandashboard.settings')->name('settings');

        // Self upsert (create/link if missing, otherwise update)
        Route::match(['post', 'put'], '/self', [GuardianDashboardController::class, 'selfUpsert'])->name('self.upsert');

        // Legacy direct update by id (kept for compatibility)
        Route::put('/{id}', [GuardianDashboardController::class, 'update'])->name('self.update');
    });
