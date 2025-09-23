<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\AdminDashboardController;
use App\Http\Controllers\Auth\FacultyDashboardController;
use App\Http\Controllers\Auth\GuardianDashboardController;


// ====================
// Login Routes
// ====================
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ====================
// Protected Dashboards
// ====================
Route::middleware(['auth'])->group(function () {

    // ====================
    // Admin Dashboard (only for admin role)
    // ====================
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
            ->name('admin.dashboard');

        // Student Management (Admin only)
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::post('/students/store', [StudentController::class, 'store'])->name('students.store');
        Route::delete('/students/{id}', [StudentController::class, 'destroy'])->name('students.destroy');

        // Faculty Management
        Route::post('/faculty/store', [FacultyDashboardController::class, 'store'])->name('faculty.store');

        // Guardian Management
        Route::post('/guardian/store', [GuardianDashboardController::class, 'store'])->name('guardian.store');
    });

    // ====================
    // Faculty Dashboard (only for faculty role)
    // ====================
    Route::middleware(['role:faculty'])->group(function () {
        Route::get('/faculty/dashboard', [FacultyDashboardController::class, 'index'])
            ->name('faculty.dashboard');
    });

    // ====================
    // Guardian Dashboard (only for guardian role)
    // ====================
    Route::middleware(['role:guardian'])->group(function () {
        Route::get('/guardian/dashboard', [GuardianDashboardController::class, 'index'])
            ->name('guardian.dashboard');
    });

    // ====================
    // Default home/dashboard for generic users
    // ====================
    Route::get('/home', function () {
        return view('home');
    })->name('home');
});
