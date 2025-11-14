<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Note: The schedules index/view remains in AdminDashboardController@schedules.
     * This controller handles admin-side CRUD only.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'day'         => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'class_start' => 'required|date_format:H:i',
            'class_end'   => 'required|date_format:H:i|after:class_start',
            'faculty_id'  => 'required|exists:faculties,id',
            'subject_id'  => 'required|exists:subjects,id',
            'gradelvl_id' => 'nullable|exists:gradelvls,id',
            'school_year' => 'nullable|string|max:9|exists:schoolyrs,school_year',
        ]);

        // normalize to HH:MM
        $data['class_start'] = substr($data['class_start'], 0, 5);
        $data['class_end']   = substr($data['class_end'],   0, 5);

        // SAFE optional
        $data['school_year'] = $request->input('school_year');

        Schedule::create($data);

        return back()->with('success', 'Schedule added successfully!');
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $data = $request->validate([
            'day'         => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'class_start' => 'required|date_format:H:i',
            'class_end'   => 'required|date_format:H:i|after:class_start',
            'faculty_id'  => 'required|exists:faculties,id',
            'subject_id'  => 'required|exists:subjects,id',
            'gradelvl_id' => 'nullable|exists:gradelvls,id',
            'school_year' => 'nullable|string|max:9|exists:schoolyrs,school_year',
        ]);

        // normalize to HH:MM
        $data['class_start'] = substr($data['class_start'], 0, 5);
        $data['class_end']   = substr($data['class_end'],   0, 5);

        // SAFE optional
        $data['school_year'] = $request->input('school_year');

        $schedule->update($data);

        return back()->with('success', 'Schedule updated successfully!');
    }

    public function destroy($id)
    {
        Schedule::findOrFail($id)->delete();
        return back()->with('success', 'Schedule deleted successfully!');
    }
}
