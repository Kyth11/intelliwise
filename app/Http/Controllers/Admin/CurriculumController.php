<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Curriculum;
use App\Models\Gradelvl;
use App\Models\Schoolyr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurriculumController extends Controller
{
    public function index()
    {
        $currentSchoolYear = Schoolyr::where('active', 1)->first();

        // For the checkbox list and the “Active SY” badge
        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get();

        // Grade levels with subjects for the right-hand panel and the modal
        $gradelvls = Gradelvl::with(['subjects' => function ($q) {
            $q->orderBy('subject_name');
        }])->orderBy('id')->get();

        // Curriculum list for the left-hand table
        $result = Curriculum::with(['schoolYear', 'grade'])
            ->where('deleted', 0)
            ->orderByDesc('id')
            ->get()
            ->map(function (Curriculum $c) {
                // add accessors that the Blade expects: school_year, grade_level
                $c->school_year = optional($c->schoolYear)->school_year;
                $c->grade_level = optional($c->grade)->grade_level;

                return $c;
            });

        return view('auth.admindashboard.curriculum', compact(
            'currentSchoolYear',
            'result',
            'gradelvls',
            'schoolyrs'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'curriculum_name' => ['required', 'string', 'max:255'],

            // optional grade_id (your migration allows nullable)
            'grade_id'        => ['nullable', 'integer', 'exists:gradelvls,id'],

            // school year checkboxes
            'schoolyr_ids'    => ['nullable', 'array'],
            'schoolyr_ids.*'  => ['integer', 'exists:schoolyrs,id'],

            // subjects[] hidden inputs in the modal
            'subjects'        => ['nullable', 'array'],
            'subjects.*'      => ['integer', 'exists:subjects,id'],
        ]);

        // If no SY was explicitly checked, fall back to the active SY (if any)
        $schoolyrIds = $validated['schoolyr_ids'] ?? [];
        if (empty($schoolyrIds)) {
            $active = Schoolyr::where('active', 1)->first();
            if ($active) {
                $schoolyrIds = [$active->id];
            }
        }

        DB::transaction(function () use ($schoolyrIds, $validated) {
            foreach ($schoolyrIds as $syId) {
                // Create one curriculum per selected school year
                $curriculum = Curriculum::create([
                    'curriculum_name' => $validated['curriculum_name'],
                    'schoolyr_id'     => $syId,
                    'grade_id'        => $validated['grade_id'] ?? null,
                    'status'          => 1,
                    'deleted'         => 0,
                ]);

                // Attach multiple subjects via curriculum_child pivot
                if (!empty($validated['subjects'])) {
                    $attachData = [];

                    foreach ($validated['subjects'] as $subjectId) {
                        $attachData[$subjectId] = [
                            'deleted'      => 0,
                            'status'       => 1,
                            'day_schedule' => null,
                            'class_start'  => null,
                            'class_end'    => null,
                        ];
                    }

                    // uses Curriculum::subjects() belongsToMany()
                    $curriculum->subjects()->attach($attachData);
                }
            }
        });

        return redirect()
            ->route('admin.curriculum.index')
            ->with('success', 'Curriculum saved.');
    }

    public function updateStatus(Request $request, Curriculum $curriculum)
    {
        $data = $request->validate([
            'status' => ['required', 'in:0,1'],
        ]);

        $curriculum->update([
            'status' => (int) $data['status'],
        ]);

        return back()->with('success', 'Curriculum status updated.');
    }

    public function destroy(Curriculum $curriculum)
    {
        DB::transaction(function () use ($curriculum) {
            // soft-delete via the tinyint flags
            $curriculum->update([
                'deleted' => 1,
                'status'  => 0,
            ]);

            // mark its children as deleted as well
            $curriculum->children()->update([
                'deleted' => 1,
                'status'  => 0,
            ]);
        });

        return back()->with('success', 'Curriculum archived.');
    }

    public function edit(Curriculum $curriculum)
    {
        // You can extend this later; this just gives you a working stub
        $curriculum->load(['subjects', 'schoolYear', 'grade']);

        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get();
        $gradelvls = Gradelvl::with('subjects')->orderBy('id')->get();

        return view('auth.admindashboard.curriculum_edit', compact(
            'curriculum',
            'schoolyrs',
            'gradelvls'
        ));
    }
}
