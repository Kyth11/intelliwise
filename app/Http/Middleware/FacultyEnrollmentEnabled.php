<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AppSetting;

class FacultyEnrollmentEnabled
{
    public function handle(Request $request, Closure $next)
    {
        $globallyEnabled = (bool) AppSetting::get('faculty_enrollment_enabled', true);

        if (!$globallyEnabled) {
            return redirect()
                ->route('faculty.dashboard')
                ->with('error', 'Enrollment is currently disabled by the administrator.');
        }

        return $next($request);
    }
}
