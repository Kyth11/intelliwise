<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schoolyr;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\Faculty;
use App\Models\Tuition;
use App\Models\Subjects;
use App\Models\Enrollment;
use App\Models\OptionalFee;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SchoolYearController extends Controller
{
    /**
     * Proceed current school year to the next school year.
     * POST /admin/settings/school-year/{id}/proceed
     */
    public function proceed(Request $request, $id)
    {

        // dd($request);
        // $this->authorize('manage-settings'); // adjust to your gate/permission or middleware
       
        $current = Schoolyr::findOrFail($id);

        // parse "YYYY-YYYY" pattern
        if (! preg_match('/^(\d{4})\-(\d{4})$/', $current->school_year, $m)) {
            return back()->with('error', 'Current school year label not in expected format YYYY-YYYY.');
        }

        $current->update([
            'active'        => 0,
        ]);




        $start = (int) $m[1];
        $end = (int) $m[2];

        $nextStart = $start + 1;
        $nextEnd = $end + 1;
        $nextLabel = sprintf('%04d-%04d', $nextStart, $nextEnd);

        // create or find next school year
        $next = Schoolyr::firstOrCreate(['school_year' => $nextLabel]);
        // Update fields
        $next->update([
            'active'        => 1,
        ]);
        DB::beginTransaction();
   


        try {
            // Mapping arrays to remap FK relationships while duplicating
            // $guardianMap = [];
            // $facultyMap = [];
            // $tuitionMap = [];
            // $subjectMap = [];
            // $studentMap = [];
            // $optionalFeeMap = [];

            // // 1) Clone guardians (soft-delete original after clones)
            // $guardians = Guardian::where('schoolyr_id', $current->id)->orWhereNull('schoolyr_id')->get(); // include those without SY assigned
            // foreach ($guardians as $g) {
            //     $clone = $g->replicate();
            //     $clone->schoolyr_id = $next->id;
            //     $clone->push();
            //     $guardianMap[$g->id] = $clone->id;
            //     // soft delete original
            //     $g->delete();
            // }

            // // 2) Clone faculties
            // $faculties = Faculty::where('schoolyr_id', $current->id)->orWhereNull('schoolyr_id')->get();
            // foreach ($faculties as $f) {
            //     $clone = $f->replicate();
            //     $clone->schoolyr_id = $next->id;
            //     $clone->push();
            //     $facultyMap[$f->id] = $clone->id;
            //     $f->delete();
            // }

            // // 3) Clone tuitions (matching by school_year text if present)
            // $tuitions = Tuition::where('school_year', $current->school_year)
            //             ->orWhere('schoolyr_id', $current->id)
            //             ->get();
            // foreach ($tuitions as $t) {
            //     $clone = $t->replicate();
            //     $clone->school_year = $next->school_year; // update textual label
            //     $clone->schoolyr_id = $next->id;
            //     $clone->push();
            //     $tuitionMap[$t->id] = $clone->id;
            //     $t->delete();
            // }

            // // 4) Clone optional fees (if any)
            // if (class_exists(OptionalFee::class)) {
            //     $optFees = OptionalFee::where('schoolyr_id', $current->id)->orWhereNull('schoolyr_id')->get();
            //     foreach ($optFees as $of) {
            //         $clone = $of->replicate();
            //         $clone->schoolyr_id = $next->id;
            //         $clone->push();
            //         $optionalFeeMap[$of->id] = $clone->id;
            //         $of->delete();
            //     }
            // }

            // // 5) Clone subjects (curriculum) — subjects are usually grade-level scoped
            // $subjects = Subjects::where('schoolyr_id', $current->id)->orWhereNull('schoolyr_id')->get();
            // foreach ($subjects as $s) {
            //     $clone = $s->replicate();
            //     $clone->schoolyr_id = $next->id;
            //     $clone->push();
            //     $subjectMap[$s->id] = $clone->id;
            //     $s->delete();
            // }

            // // 6) Clone students (and remap guardian_id)
            // $students = Student::where('schoolyr_id', $current->id)->orWhereNull('schoolyr_id')->get();
            // foreach ($students as $st) {
            //     $clone = $st->replicate();

            //     // remap guardian if present
            //     if ($st->guardian_id && isset($guardianMap[$st->guardian_id])) {
            //         $clone->guardian_id = $guardianMap[$st->guardian_id];
            //     }

            //     // remap tuition if present
            //     if ($st->tuition_id && isset($tuitionMap[$st->tuition_id])) {
            //         $clone->tuition_id = $tuitionMap[$st->tuition_id];
            //     }

            //     $clone->schoolyr_id = $next->id;
            //     $clone->push();
            //     $studentMap[$st->id] = $clone->id;
            //     $st->delete();
            // }

            // // 7) Clone enrollments — snapshot balances carried over
            // $enrollments = Enrollment::where('schoolyr_id', $current->id)->get();
            // foreach ($enrollments as $en) {
            //     $clone = $en->replicate();

            //     // remap student/guardian/tuition/gradelvl/faculty
            //     if ($en->student_id && isset($studentMap[$en->student_id])) {
            //         $clone->student_id = $studentMap[$en->student_id];
            //     }
            //     if ($en->guardian_id && isset($guardianMap[$en->guardian_id])) {
            //         $clone->guardian_id = $guardianMap[$en->guardian_id];
            //     }
            //     if ($en->tuition_id && isset($tuitionMap[$en->tuition_id])) {
            //         $clone->tuition_id = $tuitionMap[$en->tuition_id];
            //     }
            //     if ($en->faculty_id && isset($facultyMap[$en->faculty_id])) {
            //         $clone->faculty_id = $facultyMap[$en->faculty_id];
            //     }

            //     // set new schoolyr
            //     $clone->schoolyr_id = $next->id;

            //     // we intentionally keep base_tuition/optional_total/total_due snapshot
            //     // and keep paid_to_date/balance_cached as-is so admin can see amounts carried forward.
            //     $clone->push();

            //     // soft delete original enrollment
            //     $en->delete();
            // }

            // // 8) Grades: archive (soft delete); do NOT duplicate
            // $grades = Grade::where('schoolyr_id', $current->id)->get();
            // foreach ($grades as $g) {
            //     $g->delete();
            // }

            DB::commit();

            return redirect()->route('admin.settings.index')->with('success', "Proceeded to {$next->school_year}. Old records were archived (soft deleted) and clones created for the new school year.");
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to proceed to next SY: '.$e->getMessage());
        }
    }
}
