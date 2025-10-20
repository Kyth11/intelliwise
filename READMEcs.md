# Laravel 12 Admin Split (Grouped routes)

This package adds **dedicated Admin controllers** under `App\Http\Controllers\Admin` and updates `routes/web.php` to use **grouped, tidy routes** while keeping all dashboard views in your existing `Auth\*DashboardController`s.

## What’s included

**New controllers (Admin-only CRUD):**
- `Admin\AnnouncementController` — store, update, destroy
- `Admin\ScheduleController` — store, update, destroy (index view stays in AdminDashboardController@schedules)
- `Admin\TuitionController` — store, update, destroy
- `Admin\OptionalFeeController` — store, update, destroy
- `Admin\SubjectController` — store, update, destroy
- `Admin\SettingController` — updateSystem, storeAdmin, destroyAdmin, storeSchoolYear
- `Admin\GradeQuarterController` — (as you had it)

**Middlewares (unchanged, included here for completeness):**
- `App\Http\Middleware\RoleMiddleware`
- `App\Http\Middleware\FacultyEnrollmentEnabled`

**Routes**
- `routes/web.php` rewritten to **group by resource** under `/admin/*` without changing your blades’ POST/PUT/DELETE paths. For example, `/admin/announcements/store` still works, now points to `Admin\AnnouncementController@store`.

## Install

1. **Back up** your current project.
2. Copy the files from this zip into your Laravel app root. Overwrite `routes/web.php` when prompted.
3. Ensure your `app/Http/Kernel.php` has middleware aliases:
   ```php
   protected $routeMiddleware = [
       // ...
       'role' => \App\Http\Middleware\RoleMiddleware::class,
       'enrollment.open' => \App\Http\Middleware\FacultyEnrollmentEnabled::class,
   ];
   ```
4. No changes to your Blade views are required.

If you want to move the **view methods** (like `settings()` and `schedules()` pages) into dedicated controllers later, you can — for now they remain in `AdminDashboardController` per your instruction.
