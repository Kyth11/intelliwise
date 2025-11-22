<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schoolyr;
use Illuminate\Http\Request;

class SchoolYearController extends Controller
{
    /**
     * POST /admin/settings/school-year/{id}/proceed
     * Make the next school year (by label) active.
     */
    public function proceed(Request $request, $id)
    {
        // If you have an admin middleware already, you do not need explicit authorize()
        // $this->authorize('manage-settings');

        $current = Schoolyr::findOrFail($id);

        // Find the next school year label (e.g., from 2025-2026 to 2026-2027)
        $next = Schoolyr::where('school_year', '>', $current->school_year)
            ->orderBy('school_year', 'asc')
            ->first();

        if (! $next) {
            return back()->with('error', 'No next school year is defined after ' . $current->school_year . '.');
        }

        // Deactivate all, then activate next
        Schoolyr::query()->update(['active' => false]);
        $next->active = true;
        $next->save();

        return back()->with('success', 'Active school year updated to ' . $next->school_year . '.');
    }

    /**
     * POST /admin/settings/school-year/{id}/revert
     * Make the previous school year (by label) active again.
     */
    public function revert(Request $request, $id)
    {
        // $this->authorize('manage-settings');

        $current = Schoolyr::findOrFail($id);

        // Find the previous school year label
        $prev = Schoolyr::where('school_year', '<', $current->school_year)
            ->orderBy('school_year', 'desc')
            ->first();

        if (! $prev) {
            return back()->with('error', 'No previous school year is defined before ' . $current->school_year . '.');
        }

        // Deactivate all, then activate previous
        Schoolyr::query()->update(['active' => false]);
        $prev->active = true;
        $prev->save();

        return back()->with('success', 'Active school year reverted to ' . $prev->school_year . '.');
    }
}
