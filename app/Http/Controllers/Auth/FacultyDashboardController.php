<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Faculty;
use App\Models\Schedule;
use App\Models\Subjects;
use App\Models\Section;
use App\Models\Rooms;
use App\Models\Gradelvl;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FacultyDashboardController extends Controller
{
    /**
     * Show all faculties with their schedules and assignments.
     */
    public function index()
    {
        $faculties = Faculty::with([
            'user',
            'schedules.subject',
            'schedules.section',
            'schedules.room',
            'schedules.gradelvl'
        ])->get();

        // needed for edit schedule modal
        $subjects   = Subjects::all();
        $sections   = Section::all();
        $rooms      = Rooms::all();
        $gradelvls  = Gradelvl::all();

        return view('auth.admindashboard.faculties', compact(
            'faculties',
            'subjects',
            'sections',
            'rooms',
            'gradelvls'
        ));
    }

    /**
     * Store a new faculty account (admin only).
     */
    public function store(Request $request)
    {
        $request->validate([
            'f_firstname' => 'required|string|max:255',
            'f_lastname'  => 'required|string|max:255',
            'f_email'     => 'nullable|email|max:255|unique:faculties,f_email',
            'username'    => 'required|string|max:255|unique:users,username',
            'password'    => 'required|string|min:6',
        ]);

        $faculty = Faculty::create([
            'f_firstname'  => $request->f_firstname,
            'f_middlename' => $request->f_middlename,
            'f_lastname'   => $request->f_lastname,
            'f_address'    => $request->f_address,
            'f_contact'    => $request->f_contact,
            'f_email'      => $request->f_email,
        ]);

        User::create([
            'name'       => $faculty->f_firstname . ' ' . $faculty->f_lastname,
            'username'   => $request->username,
            'password'   => bcrypt($request->password),
            'role'       => 'faculty',
            'faculty_id' => $faculty->id,
        ]);

        return back()->with('success', 'Faculty account created successfully!');
    }

    /**
     * Edit faculty form.
     */
    public function edit($id)
    {
        $faculty = Faculty::with(['user', 'schedules'])->findOrFail($id);
        return view('auth.editfaculty', compact('faculty'));
    }

    /**
     * Update faculty account (admin or self).
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // 🔒 If faculty role, only allow self-update
        if ($user->role === 'faculty' && $user->faculty_id != $id) {
            abort(403, 'Unauthorized');
        }

        $faculty = Faculty::with('user')->findOrFail($id);

        $request->validate([
            'f_firstname' => 'required|string|max:255',
            'f_lastname'  => 'required|string|max:255',
            'f_email'     => 'nullable|email|max:255|unique:faculties,f_email,' . $faculty->id,
            'username'    => 'required|string|max:255|unique:users,username,' . ($faculty->user->id ?? 'NULL'),
            'password'    => 'nullable|string|min:6',
        ]);

        $faculty->update([
            'f_firstname'  => $request->f_firstname,
            'f_middlename' => $request->f_middlename,
            'f_lastname'   => $request->f_lastname,
            'f_address'    => $request->f_address,
            'f_contact'    => $request->f_contact,
            'f_email'      => $request->f_email,
        ]);

        if ($faculty->user) {
            $faculty->user->update([
                'name'     => $faculty->f_firstname . ' ' . $faculty->f_lastname,
                'username' => $request->username,
                'password' => $request->filled('password')
                    ? bcrypt($request->password)
                    : $faculty->user->password,
            ]);
        }

        return back()->with('success', 'Faculty updated successfully!');
    }
    /**
     * Update faculty schedule (admin only).
     */
    public function updateSchedule(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $request->validate([
            'subject_id'  => 'required|exists:subjects,id',
            'gradelvl_id' => 'required|exists:gradelvls,id',
            'section_id'  => 'required|exists:sections,id',
            'room_id'     => 'required|exists:rooms,id',
            'day'         => 'required|string|max:50',
            'class_start' => 'required|date_format:H:i',
            'class_end'   => 'required|date_format:H:i|after:class_start',
        ]);

        $schedule->update([
            'subject_id'  => $request->subject_id,
            'gradelvl_id' => $request->gradelvl_id,
            'section_id'  => $request->section_id,
            'room_id'     => $request->room_id,
            'day'         => $request->day,
            'class_start' => $request->class_start,
            'class_end'   => $request->class_end,
        ]);

        return back()->with('success', 'Schedule updated successfully!');
    }

    /**
     * Delete faculty (admin only).
     */
    public function destroy($id)
    {
        $faculty = Faculty::findOrFail($id);

        User::where('faculty_id', $faculty->id)->delete();
        $faculty->delete();

        return back()->with('success', 'Faculty account deleted successfully!');
    }
}
