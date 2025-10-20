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
        $data = $request->validate([
            'subject_name' => ['required','string','max:255'],
            'subject_code' => ['required','string','max:50','unique:subjects,subject_code'],
            'description'  => ['nullable','string','max:1000'],
            'gradelvl_id'  => ['required','exists:gradelvls,id'],
        ]);

        Subjects::create($data);

        return back()->with('success', 'Subject added.');
    }

    public function update(Request $request, $id)
    {
        $subject = Subjects::findOrFail($id);

        $data = $request->validate([
            'subject_name' => ['required','string','max:255'],
            'subject_code' => [
                'required','string','max:50',
                Rule::unique('subjects','subject_code')->ignore($subject->id),
            ],
            'description'  => ['nullable','string','max:1000'],
            'gradelvl_id'  => ['required','exists:gradelvls,id'],
        ]);

        $subject->update($data);

        return back()->with('success', 'Subject updated.');
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
