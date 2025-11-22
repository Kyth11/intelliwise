<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Store multiple schedule rows coming from the Add Schedule modal.
     *
     * Request structure (from scheduleModal.blade.php):
     * - faculty_id    (top select, required)
     * - gradelvl_id   (top select, required)
     * - entries[]: array of
     *      - entries[*][subject_id]
     *      - entries[*][day]
     *      - entries[*][class_start]
     *      - entries[*][class_end]
     * - school_year   (optional / hidden or set in controller elsewhere)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'faculty_id'           => 'required|exists:faculties,id',
            'gradelvl_id'          => 'required|exists:gradelvls,id',

            'entries'              => 'required|array|min:1',

            'entries.*.subject_id' => 'required|exists:subjects,id',
            'entries.*.day'        => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'entries.*.class_start'=> 'required|date_format:H:i',
            'entries.*.class_end'  => 'required|date_format:H:i',

            // optional, may be null or set from System Settings elsewhere
            'school_year'          => 'nullable|string|max:9|exists:schoolyrs,school_year',
        ]);

        $facultyId  = $validated['faculty_id'];
        $gradelvlId = $validated['gradelvl_id'];
        $entries    = $validated['entries'];
        $schoolYear = $request->input('school_year'); // may be null

        foreach ($entries as $entry) {
            // simple server-side check: end must be after start
            if ($entry['class_end'] <= $entry['class_start']) {
                // you can instead throw a ValidationException; here we just skip invalid rows
                continue;
            }

            $start = substr($entry['class_start'], 0, 5); // HH:MM
            $end   = substr($entry['class_end'],   0, 5); // HH:MM

            Schedule::create([
                'faculty_id'   => $facultyId,
                'gradelvl_id'  => $gradelvlId,
                'subject_id'   => $entry['subject_id'],
                'day'          => $entry['day'],
                'class_start'  => $start,
                'class_end'    => $end,
                'school_year'  => $schoolYear,
            ]);
        }

        return back()->with('success', 'Schedule(s) added successfully!');
    }

    /**
     * Update a single schedule row from the Edit Schedule modal.
     *
     * Request structure (per schedule):
     * - day
     * - class_start
     * - class_end
     * - faculty_id
     * - subject_id
     * - gradelvl_id
     * - school_year (optional)
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $data = $request->validate([
            'day'         => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'class_start' => 'required|date_format:H:i',
            'class_end'   => 'required|date_format:H:i|after:class_start',

            'faculty_id'  => 'required|exists:faculties,id',
            'subject_id'  => 'required|exists:subjects,id',
            'gradelvl_id' => 'required|exists:gradelvls,id',

            'school_year' => 'nullable|string|max:9|exists:schoolyrs,school_year',
        ]);

        $data['class_start'] = substr($data['class_start'], 0, 5);
        $data['class_end']   = substr($data['class_end'],   0, 5);

        // keep old school_year if none provided
        $data['school_year'] = $request->input('school_year', $schedule->school_year);

        $schedule->update($data);

        return back()->with('success', 'Schedule updated successfully!');
    }

    /**
     * Delete a single schedule row.
     */
    public function destroy($id)
    {
        Schedule::findOrFail($id)->delete();

        return back()->with('success', 'Schedule deleted successfully!');
    }
}
