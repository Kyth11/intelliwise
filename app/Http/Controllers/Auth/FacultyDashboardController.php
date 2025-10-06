<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Faculty;
use App\Models\Schedule;
use App\Models\Subjects;
use App\Models\Gradelvl;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FacultyDashboardController extends Controller
{
    /**
     * Smart entry:
     * - If the logged-in user is a FACULTY → show the faculty dashboard (auth/facultydashboard.blade.php)
     * - Otherwise (admin)          → show the admin faculties management page (auth/admindashboard/faculties.blade.php)
     */
    public function index()
    {
        $user = Auth::user();

        // FACULTY: personal dashboard
        if ($user && $user->role === 'faculty') {
            $faculty = Faculty::with([
                'user',
                'schedules.subject',
                'schedules.section',
                'schedules.gradelvl',
            ])->find($user->faculty_id);

            // You can pass quick stats if your dashboard needs them; otherwise just $faculty is fine.
            $stats = [
                'assignments_count' => 0,   // replace if you have an assignments table
                'students_count'    => 0,   // replace when you wire student->faculty relations
                'messages_count'    => 0,   // replace if you track messages
            ];

            // resources/views/auth/facultydashboard.blade.php (your faculty UI)
            return view('auth.facultydashboard', compact('faculty', 'stats'));
        }

        // ADMIN: faculties management list
        $faculties = Faculty::with([
            'user',
            'schedules.subject',
            'schedules.section',
            'schedules.gradelvl',
        ])->get();

        $subjects  = Subjects::all();
        $gradelvls = Gradelvl::all();

        // resources/views/auth/admindashboard/faculties.blade.php (your existing admin page)
        return view('auth.admindashboard.faculties', compact('faculties', 'subjects', 'gradelvls'));
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
     * Edit faculty form (admin).
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

        // If faculty role, only allow self-update
        if ($user->role === 'faculty' && (int) $user->faculty_id !== (int) $id) {
            abort(403, 'Unauthorized');
        }

        $faculty = Faculty::with('user')->findOrFail($id);

        $request->validate([
            'f_firstname' => 'required|string|max:255',
            'f_lastname'  => 'required|string|max:255',
            'f_email'     => 'nullable|email|max:255|unique:faculties,f_email,' . $faculty->id,
            'username'    => 'required|string|max:255|unique:users,username,' . optional($faculty->user)->id,
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
     * Update a faculty member’s schedule (admin only).
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
            'gradelvl_id' => $request->gradelvls_id ?? $request->gradelvl_id, // tolerate either key
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
