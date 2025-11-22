<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schoolyr;
use App\Models\Gradelvl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurriculumController extends Controller
{
    /**
     * GET /admin/curriculum
     * Route name: admin.curriculum.index
     */
    public function index()
    {
        $currentSchoolYear = Schoolyr::where('active', 1)->first();

        // Main list
        $query = DB::table('curriculum as c')
            ->leftJoin('schoolyrs as sy', 'sy.id', '=', 'c.schoolyr_id')
            ->leftJoin('gradelvls as g', 'g.id', '=', 'c.grade_id')
            ->where('c.deleted', 0);

        // Show rows for active SY, but also show "global" curriculums with NULL schoolyr_id
        if ($currentSchoolYear) {
            $query->where(function ($q) use ($currentSchoolYear) {
                $q->where('c.schoolyr_id', $currentSchoolYear->id)
                  ->orWhereNull('c.schoolyr_id');
            });
        }

        $result = $query
            ->select(
                'c.id',
                'c.curriculum_name',
                'c.status',
                'sy.school_year',
                'g.grade_level'
            )
            ->orderBy('sy.school_year', 'desc')
            ->orderBy('c.curriculum_name', 'asc')
            ->get();

        // For School Year checkboxes in the modal
        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get();

        // For “Subjects by Grade Level” card and modal subject listing
        $gradelvls = Gradelvl::with(['subjects' => function ($q) {
                $q->orderBy('subject_name');
            }])
            ->orderBy('id')
            ->get();

        // Faculties (if you later add adviser selection)
        $faculties = DB::table('faculties')
            ->orderBy('f_lastname')
            ->orderBy('f_firstname')
            ->get();

        $child = null; // none on index view

        return view('auth.admindashboard.curriculum', compact(
            'result',
            'schoolyrs',
            'gradelvls',
            'faculties',
            'child',
            'currentSchoolYear'
        ));
    }

    /**
     * POST /admin/curriculum
     * Route name: admin.curriculum.store
     *
     * Handles:
     * 1) Create from index modal (curriculum_name + schoolyr_ids[] + subjects[])
     * 2) Create/update from edit page (itemlist[data][…])
     */
    public function store(Request $request)
    {
        /**
         * BRANCH 1: Create via index modal (your current curriculum.blade.php)
         * Uses:
         *  - curriculum_name
         *  - schoolyr_ids[]  (checkboxes)
         *  - subjects[]      (hidden inputs in the subjects table)
         */
        if ($request->has('schoolyr_ids') && !$request->filled('id')) {
            $request->validate([
                'curriculum_name' => ['required', 'string', 'max:255'],
                'schoolyr_ids'    => ['required', 'array', 'min:1'],
                'schoolyr_ids.*'  => ['integer', 'exists:schoolyrs,id'],
                'subjects'        => ['nullable', 'array'],
                'subjects.*'      => ['integer', 'exists:subjects,id'],
            ]);

            $name   = $request->input('curriculum_name');
            $syIds  = $request->input('schoolyr_ids', []);
            $subs   = $request->input('subjects', []);
            $status = (int) $request->input('status', 1); // default Active

            DB::beginTransaction();

            try {
                foreach ($syIds as $syId) {
                    // One curriculum row per selected School Year
                    $curriculumId = DB::table('curriculum')->insertGetId([
                        'curriculum_name' => $name,
                        'schoolyr_id'     => $syId,
                        'grade_id'        => null,
                        'adviser_id'      => null,
                        'deleted'         => 0,
                        'status'          => $status,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    // Attach all subjects as curriculum_child rows
                    foreach ($subs as $subjectId) {
                        DB::table('curriculum_child')->insert([
                            'curriculum_id' => $curriculumId,
                            'subject_id'    => $subjectId,
                            'deleted'       => 0,
                            'status'        => 1,
                            'day_schedule'  => null,
                            'class_start'   => null,
                            'class_end'     => null,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);
                    }
                }

                DB::commit();

                return redirect()
                    ->route('admin.curriculum.index')
                    ->with('success', 'Curriculum created.');
            } catch (\Throwable $e) {
                DB::rollBack();

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', $e->getMessage());
            }
        }

        /**
         * BRANCH 2: Create/Update via EDIT page
         * Uses:
         *  - id (for update)
         *  - schoolyr_id, grade_id, adviser_id
         *  - curriculum_name
         *  - itemlist[data][key][subject_id / id / deleted]
         */
        $request->validate([
            'curriculum_name' => ['required', 'string', 'max:255'],
            'schoolyr_id'     => ['nullable', 'integer', 'exists:schoolyrs,id'],
            'grade_id'        => ['nullable', 'integer', 'exists:gradelvls,id'],
            'adviser_id'      => ['nullable', 'integer', 'exists:faculties,id'],
        ]);

        $schoolyr       = $request->schoolyr_id;
        $grade          = $request->grade_id;
        $adviser        = $request->adviser_id;
        $curriculumName = $request->curriculum_name;
        $status         = (int) $request->input('status', 1);
        $items          = $request->input('itemlist.data', []); // may be empty

        DB::beginTransaction();

        try {
            if ($request->filled('id')) {
                // UPDATE header
                $id = $request->id;

                DB::table('curriculum')
                    ->where('id', $id)
                    ->update([
                        'schoolyr_id'     => $schoolyr,
                        'grade_id'        => $grade,
                        'adviser_id'      => $adviser,
                        'curriculum_name' => $curriculumName,
                        'status'          => $status,
                        'updated_at'      => now(),
                    ]);

                // UPDATE / INSERT children
                foreach ($items as $row) {
                    $subjectId = $row['subject_id'] ?? null;
                    if (!$subjectId) {
                        continue;
                    }

                    if (!empty($row['id'])) {
                        DB::table('curriculum_child')
                            ->where('id', $row['id'])
                            ->update([
                                'subject_id' => $subjectId,
                                'deleted'    => $row['deleted'] ?? 0,
                                'updated_at' => now(),
                            ]);
                    } else {
                        DB::table('curriculum_child')->insert([
                            'curriculum_id' => $id,
                            'subject_id'    => $subjectId,
                            'deleted'       => 0,
                            'status'        => 1,
                            'day_schedule'  => null,
                            'class_start'   => null,
                            'class_end'     => null,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);
                    }
                }
            } else {
                // CREATE header from edit-style page
                $id = DB::table('curriculum')->insertGetId([
                    'schoolyr_id'     => $schoolyr,
                    'grade_id'        => $grade,
                    'adviser_id'      => $adviser,
                    'curriculum_name' => $curriculumName,
                    'status'          => $status,
                    'deleted'         => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                foreach ($items as $row) {
                    $subjectId = $row['subject_id'] ?? null;
                    if (!$subjectId) {
                        continue;
                    }

                    DB::table('curriculum_child')->insert([
                        'curriculum_id' => $id,
                        'subject_id'    => $subjectId,
                        'deleted'       => 0,
                        'status'        => 1,
                        'day_schedule'  => null,
                        'class_start'   => null,
                        'class_end'     => null,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.curriculum.index')
                ->with('success', 'Curriculum saved.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * GET /admin/curriculum/{id}/edit
     * Route name: admin.curriculum.curriculum_edit
     */
    public function edit($id)
    {
        $result = DB::table('curriculum')->where('id', $id)->first();

        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get();
        $gradelvls = DB::table('gradelvls')->orderBy('grade_level')->get();

        $faculties = DB::table('faculties')
            ->orderBy('f_lastname')
            ->orderBy('f_firstname')
            ->get();

        $subjects = DB::table('subjects as s')
            ->join('gradelvls as g', 'g.id', '=', 's.gradelvl_id')
            ->select(
                's.id',
                's.subject_code',
                's.subject_name',
                's.gradelvl_id',
                'g.grade_level'
            )
            ->whereNull('s.deleted_at')
            ->orderBy('g.id')
            ->orderBy('s.subject_name')
            ->get();

        $child = DB::table('curriculum_child as cc')
            ->leftJoin('subjects as s', 's.id', '=', 'cc.subject_id')
            ->leftJoin('gradelvls as g', 'g.id', '=', 's.gradelvl_id')
            ->where('cc.curriculum_id', $id)
            ->where('cc.deleted', 0)
            ->select(
                'cc.id',
                'cc.subject_id',
                's.subject_code',
                's.subject_name',
                'g.grade_level'
            )
            ->get();

        $currentSchoolYear = Schoolyr::where('active', 1)->first();

        // adjust to your actual edit view path
        return view('auth.admindashboard.curriculum_edit', compact(
            'result',
            'schoolyrs',
            'gradelvls',
            'faculties',
            'subjects',
            'child',
            'currentSchoolYear'
        ));
    }

    /**
     * PATCH /admin/curriculum/{id}/status
     * Route name: admin.curriculum.updateStatus
     */
    public function updateStatus($id, Request $request)
    {
        $request->validate([
            'status' => ['required', 'in:0,1'],
        ]);

        DB::table('curriculum')
            ->where('id', $id)
            ->update([
                'status'     => (int) $request->status,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Curriculum status updated.');
    }

    /**
     * DELETE /admin/curriculum/{id}
     * Route name: admin.curriculum.destroy
     */
    public function destroy($id)
    {
        DB::table('curriculum')
            ->where('id', $id)
            ->update([
                'deleted'    => 1,
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Curriculum deleted.');
    }
}
