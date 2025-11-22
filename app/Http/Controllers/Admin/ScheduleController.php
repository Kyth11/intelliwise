<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Schoolyr;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Store multiple schedule rows coming from the Add Schedule modal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'faculty_id'           => 'required|exists:faculties,id',
            'gradelvl_id'          => 'required|exists:gradelvls,id',

            'entries'              => 'required|array|min:1',

            'entries.*.subject_id' => 'required|exists:subjects,id',
            'entries.*.day'        => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'entries.*.class_start'=> 'required|date_format:H:i',
            'entries.*.class_end'  => 'required|date_format:H:i',

            // optional: if given, must exist; if not, we override with active SY
            'school_year'          => 'nullable|string|max:9|exists:schoolyrs,school_year',
        ]);

        $facultyId  = $validated['faculty_id'];
        $gradelvlId = $validated['gradelvl_id'];
        $entries    = $validated['entries'];

        // Determine school year: use provided or current active
        $schoolYear = $validated['school_year'] ?? null;
        if (!$schoolYear) {
            $active = Schoolyr::where('active', 1)->first();
            $schoolYear = $active?->school_year; // may be null if no active SY
        }

        foreach ($entries as $entry) {
            if ($entry['class_end'] <= $entry['class_start']) {
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
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $data = $request->validate([
            'day'         => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'class_start' => 'required|date_format:H:i',
            'class_end'   => 'required|date_format:H:i|after:class_start',

            'faculty_id'  => 'required|exists:faculties,id',
            'subject_id'  => 'required|exists:subjects,id',
            'gradelvl_id' => 'required|exists:gradelvls,id',

            // keep old SY if none provided
            'school_year' => 'nullable|string|max:9|exists:schoolyrs,school_year',
        ]);

        $data['class_start'] = substr($data['class_start'], 0, 5);
        $data['class_end']   = substr($data['class_end'],   0, 5);

        // Keep existing school year unless explicitly changed
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
