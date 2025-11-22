<?php

use Illuminate\Support\Facades\Mail;

// ---------- Auth & Dashboards ----------
use Illuminate\Support\Facades\Route;
use App\Mail\ParentAccountCredentials;
use App\Http\Controllers\GradesController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PaymentsController;

// ---------- Admin CRUD (split controllers) ----------
use App\Http\Controllers\FacultyGradesController;
use App\Http\Controllers\Admin\SchoolYearController;
use App\Http\Controllers\EnrollmentReportController;
use App\Http\Controllers\Admin\GradeQuarterController;
use App\Http\Controllers\Auth\AdminDashboardController;
use App\Http\Controllers\Auth\FacultyDashboardController;
use App\Http\Controllers\Auth\GuardianDashboardController;
use App\Http\Controllers\Admin\FacultyController as AdminFacultyController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\CurriculumController;


// ---------- Shared / other controllers ----------
use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\Admin\TuitionController as AdminTuitionController;
use App\Http\Controllers\Admin\GuardianController as AdminGuardianController;
use App\Http\Controllers\Admin\ScheduleController as AdminScheduleController;
use App\Http\Controllers\Admin\OptionalFeeController as AdminOptionalFeeController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Guardian\PaymentReceiptController as GuardianPaymentReceiptController;

// Home → Login
Route::get('/', fn() => redirect()->route('login'));

// ====================
// Authentication
// ====================
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ====================
// Admin (auth + role:admin)
// Prefix: /admin    Name: admin.*
// ====================
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Landing redirect
        Route::get('/', fn() => redirect()->route('admin.dashboard'))->name('home');

        // Top-level views
        Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/finances', [AdminDashboardController::class, 'finances'])->name('finances');
        Route::get('/accounts', [AdminDashboardController::class, 'accounts'])->name('accounts');
        Route::get('/grades', [GradesController::class, 'index'])->name('grades');

        // Settings (view-only)
        Route::get('/settings', [AdminDashboardController::class, 'settings'])->name('settings.index');

        // Settings actions
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::put('/system', [AdminSettingController::class, 'updateSystem'])->name('system.update');
            Route::post('/admins', [AdminSettingController::class, 'storeAdmin'])->name('admins.store');
            Route::delete('/admins/{id}', [AdminSettingController::class, 'destroyAdmin'])->name('admins.destroy');
            Route::post('/school-year', [AdminSettingController::class, 'storeSchoolYear'])->name('schoolyear.store');
            Route::post('/gcash-qr', [AdminSettingController::class, 'uploadGcashQr'])->name('gcashqr.upload');
            Route::post('/school-year/{id}/proceed', [SchoolYearController::class, 'proceed'])->name('schoolyear.proceed');
            Route::post('/school-year/{id}/revert', [SchoolYearController::class, 'revert'])
                ->name('schoolyear.revert');
        });

        // Students
        Route::prefix('students')->name('students.')->group(function () {
            Route::get('/', [StudentController::class, 'index'])->name('index');
            Route::get('/enroll', [StudentController::class, 'create'])->name('create');
            Route::post('/store', [StudentController::class, 'store'])->name('store');
            Route::put('/{lrn}', [StudentController::class, 'update'])->name('update');
            Route::delete('/{lrn}', [StudentController::class, 'destroy'])->name('destroy');
            // Clean endpoints used by JS
            Route::get('/search', [StudentController::class, 'search'])->name('search');         // admin.students.search
            Route::get('/prefill/{id}', [StudentController::class, 'prefill'])->name('prefill'); // admin.students.prefill
        });

        // Faculties (admin account management)
        Route::prefix('faculties')->name('faculties.')->group(function () {
            Route::get('/', [AdminFacultyController::class, 'index'])->name('index');
            Route::post('/store', [AdminFacultyController::class, 'store'])->name('store');
            Route::put('/{id}', [AdminFacultyController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminFacultyController::class, 'destroy'])->name('destroy');
        });

        // Guardians (admin account management)
        Route::prefix('guardians')->name('guardians.')->group(function () {
            Route::get('/', [AdminGuardianController::class, 'index'])->name('index');
            Route::post('/store', [AdminGuardianController::class, 'store'])->name('store');
            Route::put('/{id}', [AdminGuardianController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminGuardianController::class, 'destroy'])->name('destroy');
        });

        // Announcements
        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::post('/store', [AdminAnnouncementController::class, 'store'])->name('store');
            Route::put('/{id}', [AdminAnnouncementController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminAnnouncementController::class, 'destroy'])->name('destroy');
        });

        // Schedules
        Route::prefix('schedules')->name('schedules.')->group(function () {
            Route::get('/', [AdminDashboardController::class, 'schedules'])->name('index');
            Route::post('/store', [AdminScheduleController::class, 'store'])->name('store');
            Route::put('/{id}', [AdminScheduleController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminScheduleController::class, 'destroy'])->name('destroy');
        });

        // Tuition
        Route::prefix('tuitions')->name('tuitions.')->group(function () {
            Route::post('/', [AdminTuitionController::class, 'store'])->name('store');
            Route::put('/{id}', [AdminTuitionController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminTuitionController::class, 'destroy'])->name('destroy');
        });
        Route::delete('/tuition/{id}', [AdminTuitionController::class, 'destroy'])->name('tuition.destroy');

        // Optional Fees
        Route::prefix('optional-fees')->name('optionalfees.')->group(function () {
            Route::post('/', [AdminOptionalFeeController::class, 'store'])->name('store');
            Route::put('/{id}', [AdminOptionalFeeController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminOptionalFeeController::class, 'destroy'])->name('destroy');
        });

        // Grades (global quarter toggles)
        Route::post('/grades/quarters/save', [GradeQuarterController::class, 'save'])->name('grades.quarters.save');

        // Payments (admin only)
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::post('/store', [PaymentsController::class, 'store'])->name('store');
        });

        // Reports (Admin)
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/enrollments', [EnrollmentReportController::class, 'index'])->name('enrollments');
            Route::get('/enrollments/print', [EnrollmentReportController::class, 'print'])->name('enrollments.print');
            Route::get('/enrollments/students', [EnrollmentReportController::class, 'students'])->name('enrollments.students');
            Route::get('/enrollments/export', [EnrollmentReportController::class, 'export'])->name('enrollments.export');
        });

        // Subjects
        Route::prefix('subjects')->name('subjects.')->group(function () {
            Route::post('/', [AdminSubjectController::class, 'store'])->name('store');
            Route::put('/{id}', [AdminSubjectController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminSubjectController::class, 'destroy'])->name('destroy');
        });

        // Curriculum
        Route::prefix('curriculum')->name('curriculum.')->group(function () {
        Route::get('/curriculum', [CurriculumController::class, 'index'])
            ->name('index');

        Route::post('/curriculum', [CurriculumController::class, 'store'])
            ->name('store');

        Route::patch('/curriculum/{curriculum}/status', [CurriculumController::class, 'updateStatus'])
            ->name('updateStatus');

        Route::get('/curriculum/{curriculum}/edit', [CurriculumController::class, 'edit'])
            ->name('curriculum_edit');

        Route::delete('/curriculum/{curriculum}', [CurriculumController::class, 'destroy'])
            ->name('destroy');
        });


        // Optional AJAX
        Route::get('/grades/report', [GradesController::class, 'reportAjax'])->name('grades.report');
    });

// ====================
// Faculty Panel
// ====================
Route::middleware(['auth', 'role:faculty'])
    ->prefix('faculty')
    ->name('faculty.')
    ->group(function () {
        Route::get('/', fn() => redirect()->route('faculty.dashboard'))->name('home');
        Route::get('/dashboard', [FacultyDashboardController::class, 'index'])->name('dashboard');

        Route::get('/students', [FacultyDashboardController::class, 'students'])->name('students');
        Route::get('/students/create', [StudentController::class, 'create'])->middleware('enrollment.open')->name('students.create');
        Route::post('/students', [StudentController::class, 'store'])->middleware('enrollment.open')->name('students.store');

        Route::post('/announcements', [\App\Http\Controllers\Faculty\AnnouncementController::class, 'store'])->name('announcements.store');
        Route::put('/announcements/{id}', [\App\Http\Controllers\Faculty\AnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/announcements/{id}', [\App\Http\Controllers\Faculty\AnnouncementController::class, 'destroy'])->name('announcements.destroy');

        Route::get('/schedule', [FacultyDashboardController::class, 'schedule'])->name('schedule');

        Route::view('/settings', 'auth.facultydashboard.setting')->name('settings');

        Route::put('/profile', [\App\Http\Controllers\Faculty\ProfileController::class, 'updateSelf'])->name('profile.update');
        Route::put('/profile/{id}', [\App\Http\Controllers\Faculty\ProfileController::class, 'update'])->name('profile.update.byid');

        Route::get('/grades', [FacultyGradesController::class, 'index'])->name('grades.index');
        Route::post('/grades', [FacultyGradesController::class, 'store'])->name('grades.store');
    });

// ====================
// Guardian Panel
// ====================
Route::middleware(['auth', 'role:guardian'])
    ->prefix('guardians')
    ->name('guardians.')
    ->group(function () {
        Route::get('/', fn() => redirect()->route('guardians.dashboard'))->name('home');
        Route::get('/dashboard', [GuardianDashboardController::class, 'index'])->name('dashboard');

        Route::view('/children', 'auth.guardiandashboard.children')->name('children');
        Route::get('/reports', [\App\Http\Controllers\Guardian\ReportsController::class, 'reports'])->name('reports');
        Route::view('/settings', 'auth.guardiandashboard.settings')->name('settings');

        Route::match(['post', 'put'], '/self', [\App\Http\Controllers\Guardian\ProfileController::class, 'upsert'])->name('self.upsert');
        Route::put('/{id}', [\App\Http\Controllers\Guardian\ProfileController::class, 'update'])->name('self.update');

        Route::prefix('payment-receipts')->name('payment-receipts.')->group(function () {
            Route::post('/', [GuardianPaymentReceiptController::class, 'store'])->name('store');
        });

        if (app()->environment('local')) {
            Route::get('/_test-mail', function () {
                try {
                    Mail::to('occ.davidkieth@gmail.com')->send(
                        new ParentAccountCredentials(
                            guardianName: 'Test Parent',
                            studentName: 'Test Student',
                            username: 'testparent',
                            password: 'test12345',
                            appUrl: config('app.url')
                        )
                    );
                    return response('Mail dispatched OK.', 200);
                } catch (\Throwable $e) {
                    // Show the error right in the browser so you don't have to dig through logs
                    return response('MAIL ERROR: ' . $e->getMessage(), 500);
                }
            })->name('_test-mail');
        }
    });

