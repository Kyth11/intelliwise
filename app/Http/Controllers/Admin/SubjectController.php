<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subjects;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function store(Request $request)
    {
        // Allow both single string and array input for subject_name
        $baseRules = [
            'gradelvl_id' => ['required', 'exists:gradelvls,id'],
        ];

        if (is_array($request->input('subject_name'))) {
            $rules = $baseRules + [
                'subject_name'   => ['required', 'array'],
                'subject_name.*' => ['required', 'string', 'max:255'],
            ];
        } else {
            $rules = $baseRules + [
                'subject_name' => ['required', 'string', 'max:255'],
            ];
        }

        $validated = $request->validate($rules);

        $gradeId = $validated['gradelvl_id'];

        // Normalize to array
        $subjectInput = $validated['subject_name'];
        $names = is_array($subjectInput) ? $subjectInput : [$subjectInput];

        foreach ($names as $name) {
            if (!trim($name)) {
                continue;
            }

            Subjects::create([
                'subject_name' => $name,
                'gradelvl_id'  => $gradeId,
            ]);
        }

        return back()->with('success', 'Subject(s) added.');
    }

    public function update(Request $request, $id)
    {
        $subject = Subjects::findOrFail($id);

        // Allow both single string and array input for subject_name
        $baseRules = [
            'gradelvl_id' => ['required', 'exists:gradelvls,id'],
        ];

        if (is_array($request->input('subject_name'))) {
            $rules = $baseRules + [
                'subject_name'   => ['required', 'array'],
                'subject_name.*' => ['required', 'string', 'max:255'],
            ];
        } else {
            $rules = $baseRules + [
                'subject_name' => ['required', 'string', 'max:255'],
            ];
        }

        $validated = $request->validate($rules);

        $gradeId      = $validated['gradelvl_id'];
        $subjectInput = $validated['subject_name'];
        $names        = is_array($subjectInput) ? $subjectInput : [$subjectInput];

        // First entry updates the existing subject
        $firstName = array_shift($names);

        $subject->update([
            'subject_name' => $firstName,
            'gradelvl_id'  => $gradeId,
        ]);

        // Any remaining names will be created as new subjects under the same grade
        foreach ($names as $name) {
            if (!trim($name)) {
                continue;
            }

            Subjects::create([
                'subject_name' => $name,
                'gradelvl_id'  => $gradeId,
            ]);
        }

        return back()->with('success', 'Subject(s) updated.');
    }

    public function destroy($id)
    {
        $subject = Subjects::findOrFail($id);

        try {
            $subject->delete();
            return back()->with('success', 'Subject deleted.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Cannot delete subject because it is in use.');
        }
    }
}
