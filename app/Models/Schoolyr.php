<?php

namespace App\Models;

use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schoolyr extends Model
{
    // keep fillable minimal
    protected $fillable = ['school_year','active'];

    /**
     * Existing accessor — left exactly as you had it to avoid breaking blades.
     */
    public function getDisplayLabelAttribute(): string
    {
        $label = $this->school_year
            ?? $this->display_year
            ?? $this->year
            ?? $this->name;

        if (!$label) {
            $start = $this->start_year ?? $this->from_year ?? $this->from ?? $this->year_start ?? $this->sy_start ?? null;
            $end   = $this->end_year   ?? $this->to_year   ?? $this->to   ?? $this->year_end   ?? $this->sy_end   ?? null;
            if ($start || $end) $label = trim(($start ?? '').'–'.($end ?? ''));
        }

        if (!$label) {
            $label = $this->created_at ? $this->created_at->year.'–'.($this->created_at->year + 1) : 'SY #'.$this->id;
        }

        return $label;
    }

    /* --------------------
     | Convenience relations
     | -------------------- */

    public function students(): HasMany
    {
        return $this->hasMany(\App\Models\Student::class, 'schoolyr_id');
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(\App\Models\Guardian::class, 'schoolyr_id');
    }

    public function faculties(): HasMany
    {
        return $this->hasMany(\App\Models\Faculty::class, 'schoolyr_id');
    }

    public function tuitions(): HasMany
    {
        return $this->hasMany(\App\Models\Tuition::class, 'schoolyr_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(\App\Models\Subjects::class, 'schoolyr_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(\App\Models\Enrollment::class, 'schoolyr_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(\App\Models\Grade::class, 'schoolyr_id');
    }

    /* --------------------
     | Rollover helpers
     | -------------------- */

    /**
     * Create (or find) next school year label and perform archive+clone.
     *
     * Usage:
     *   $next = $schoolyr->proceedToNext();
     *
     * Returns the new/found Schoolyr model on success.
     *
     * Throws an exception on failure.
     */
    public function proceedToNext(): self
    {
        // Ensure label is YYYY-YYYY
        if (! preg_match('/^(\d{4})\-(\d{4})$/', $this->school_year, $m)) {
            throw new \RuntimeException('Current school_year label not in expected format YYYY-YYYY.');
        }

        $start = (int) $m[1];
        $end   = (int) $m[2];

        $nextStart = $start + 1;
        $nextEnd   = $end + 1;
        $nextLabel = sprintf('%04d-%04d', $nextStart, $nextEnd);

        // create or fetch next SY
        $next = static::firstOrCreate(['school_year' => $nextLabel]);

        // If next is already same as current, still allow (idempotent)
        if ((int) $next->id === (int) $this->id) {
            return $next;
        }

        // perform archive + clone logic; will throw on error
        $this->archiveAndCloneData($next);

        return $next;
    }

    /**
     * Archive (soft-delete) current SY rows and clone required records into $next.
     *
     * IMPORTANT:
     * - This method uses the domain conventions in your schema:
     *     guardian -> student -> enrollment -> tuition
     * - Payments are left attached to original records (historical truth). Enrollment clones keep snapshots.
     * - Grades are archived (soft deleted) and NOT duplicated.
     *
     * Returns an array summary on success.
     *
     * Throws on failure.
     */
    protected function archiveAndCloneData(self $next): array
    {
        // Maps of old_id => new_id
        $guardianMap = $facultyMap = $tuitionMap = $subjectMap = $studentMap = $optionalFeeMap = [];

        DB::beginTransaction();
        try {
            // 1) Guardians
            $guardians = \App\Models\Guardian::where('schoolyr_id', $this->id)->orWhereNull('schoolyr_id')->get();
            foreach ($guardians as $g) {
                $clone = $g->replicate();
                $clone->schoolyr_id = $next->id;
                $clone->save();
                $guardianMap[$g->id] = $clone->id;
                $g->delete(); // soft-delete original
            }

            // 2) Faculties
            $faculties = \App\Models\Faculty::where('schoolyr_id', $this->id)->orWhereNull('schoolyr_id')->get();
            foreach ($faculties as $f) {
                $clone = $f->replicate();
                $clone->schoolyr_id = $next->id;
                $clone->save();
                $facultyMap[$f->id] = $clone->id;
                $f->delete();
            }

            // 3) Tuitions (match by schoolyr_id or textual school_year string)
            $tuitions = \App\Models\Tuition::where('schoolyr_id', $this->id)
                        ->orWhere('school_year', $this->school_year)
                        ->get();
            foreach ($tuitions as $t) {
                $clone = $t->replicate();
                $clone->schoolyr_id = $next->id;
                $clone->school_year = $next->school_year; // keep label consistent
                $clone->save();
                $tuitionMap[$t->id] = $clone->id;
                $t->delete();
            }

            // 4) Optional fees (if model exists)
            if (class_exists(\App\Models\OptionalFee::class)) {
                $optFees = \App\Models\OptionalFee::where('schoolyr_id', $this->id)->orWhereNull('schoolyr_id')->get();
                foreach ($optFees as $of) {
                    $clone = $of->replicate();
                    $clone->schoolyr_id = $next->id;
                    $clone->save();
                    $optionalFeeMap[$of->id] = $clone->id;
                    $of->delete();
                }
            }

            // 5) Subjects (curriculum)
            $subjects = \App\Models\Subjects::where('schoolyr_id', $this->id)->orWhereNull('schoolyr_id')->get();
            foreach ($subjects as $s) {
                $clone = $s->replicate();
                $clone->schoolyr_id = $next->id;
                $clone->save();
                $subjectMap[$s->id] = $clone->id;
                $s->delete();
            }

            // 6) Students (remap guardian & tuition)
            $students = \App\Models\Student::where('schoolyr_id', $this->id)->orWhereNull('schoolyr_id')->get();
            foreach ($students as $st) {
                $clone = $st->replicate();

                if ($st->guardian_id && isset($guardianMap[$st->guardian_id])) {
                    $clone->guardian_id = $guardianMap[$st->guardian_id];
                }

                if ($st->tuition_id && isset($tuitionMap[$st->tuition_id])) {
                    $clone->tuition_id = $tuitionMap[$st->tuition_id];
                }

                $clone->schoolyr_id = $next->id;
                $clone->save();
                $studentMap[$st->id] = $clone->id;
                $st->delete();
            }

            // 7) Enrollments (carry snapshot fields)
            $enrollments = \App\Models\Enrollment::where('schoolyr_id', $this->id)->get();
            foreach ($enrollments as $en) {
                $clone = $en->replicate();

                if ($en->student_id && isset($studentMap[$en->student_id])) {
                    $clone->student_id = $studentMap[$en->student_id];
                }
                if ($en->guardian_id && isset($guardianMap[$en->guardian_id])) {
                    $clone->guardian_id = $guardianMap[$en->guardian_id];
                }
                if ($en->tuition_id && isset($tuitionMap[$en->tuition_id])) {
                    $clone->tuition_id = $tuitionMap[$en->tuition_id];
                }
                if ($en->faculty_id && isset($facultyMap[$en->faculty_id])) {
                    $clone->faculty_id = $facultyMap[$en->faculty_id];
                }

                $clone->schoolyr_id = $next->id;

                // keep base_tuition, optional_total, total_due, paid_to_date, balance_cached as-is
                $clone->save();

                $en->delete();
            }

            // 8) Grades -> archive only
            $grades = \App\Models\Grade::where('schoolyr_id', $this->id)->get();
            foreach ($grades as $g) {
                $g->delete();
            }

            DB::commit();

            return [
                'guardians'  => count($guardianMap),
                'faculties'  => count($facultyMap),
                'tuitions'   => count($tuitionMap),
                'subjects'   => count($subjectMap),
                'students'   => count($studentMap),
                'enrollments'=> $enrollments->count(),
                'grades_archived' => $grades->count(),
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    // Ensure this controller is inside your admin middleware group in routes/web.php
    public function proceed(Request $request, $id)
    {
        // Optional: additional authorization logic if you have gates/policies
        // $this->authorize('manage-settings');

        try {
            $sy = Schoolyr::findOrFail($id);
            $next = $sy->proceedToNext(); // <-- your model method

            return redirect()->back()->with('success', "Proceeded to school year {$next->school_year}.");
        } catch (Throwable $e) {
            Log::error('SY proceed failed: '.$e->getMessage(), ['id' => $id]);
            return redirect()->back()->with('error', 'Failed to proceed to next SY: '.$e->getMessage());
        }
    }
}
