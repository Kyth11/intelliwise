<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Student;
use App\Models\Faculty;
use App\Models\Guardian;
use App\Models\Announcement;
use App\Models\Schedule;
use App\Models\Tuition;
use App\Models\Subjects;
use App\Models\Rooms;
use App\Models\Section;
use App\Models\Gradelvl;
use App\Models\Schoolyr;
use Illuminate\Support\Facades\Hash;

class AdminDashboardController extends Controller
{
    /**
     * Show admin dashboard with all necessary data for modals and lists.
     */
    public function dashboard()
    {
        $students   = Student::with('guardian')->get();
        $faculties  = Faculty::with('user')->get();
        $guardians  = Guardian::with('user')->get();

        // Order announcements by event date if set; fallback to created_at (latest first)
        $announcements = Announcement::orderByRaw('COALESCE(date_of_event, created_at) DESC')
            ->take(50)
            ->get();

        $schedules = Schedule::with(['subject', 'room', 'section', 'gradelvl', 'faculty.user'])
            ->orderBy('day')
            ->get();

        // Load ALL tuition rows so multiple school years show
        $tuitions  = Tuition::orderBy('updated_at', 'desc')->get();

        // Lists for modals
        $subjects  = Subjects::all();
        $rooms     = Rooms::all();
        $sections  = Section::all();
        $gradelvls = Gradelvl::all(); // used by announcements & schedules forms
        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get();

        return view('auth.admindashboard', compact(
            'students',
            'faculties',
            'guardians',
            'announcements',
            'schedules',
            'tuitions',
            'subjects',
            'rooms',
            'sections',
            'gradelvls',
            'schoolyrs'
        ));
    }

    // =========================
    // Students tab
    // =========================
    public function students()
    {
        $students = Student::with('guardian')->get();
        return view('auth.admindashboard.students', compact('students'));
    }

    // =========================
    // Faculties tab
    // =========================
    public function faculties()
    {
        $faculties = Faculty::with('user')->get();
        return view('auth.admindashboard.faculties', compact('faculties'));
    }

    // =========================
    // Accounts tab
    // =========================
    public function accounts()
    {
        $faculties = Faculty::with('user')->get();
        $guardians = Guardian::with('user')->get();
        return view('auth.admindashboard.accounts', compact('faculties', 'guardians'));
    }

    // =========================
    // Settings (enhanced)
    // =========================
    public function settings()
    {
        $admins    = User::where('role', 'admin')->orderBy('created_at', 'desc')->get();
        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get();

        return view('auth.admindashboard.settings', compact('admins', 'schoolyrs'));
    }

    public function storeAdmin(Request $request)
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'username'              => ['required', 'string', 'max:255', 'unique:users,username'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'role'                  => ['in:admin'], // locked to admin
        ]);

        User::create([
            'name'      => $data['name'],
            'username'  => $data['username'],
            'password'  => Hash::make($data['password']),
            'role'      => 'admin',
            'faculty_id'=> null,
            'guardian_id'=> null,
        ]);

        return back()->with('success', 'Admin account created.');
    }

    public function destroyAdmin($id)
    {
        $toDelete = User::where('id', $id)->where('role', 'admin')->firstOrFail();

        // Prevent deleting self
        if (auth()->id() === $toDelete->id) {
            return back()->with('error', "You cannot delete your own account.");
        }

        // Prevent deleting the last remaining admin
        $adminCount = User::where('role', 'admin')->count();
        if ($adminCount <= 1) {
            return back()->with('error', "Cannot delete the last admin account.");
        }

        $toDelete->delete();
        return back()->with('success', 'Admin account deleted.');
    }

    public function storeSchoolYear(Request $request)
    {
        $data = $request->validate([
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/', 'unique:schoolyrs,school_year'],
        ]);

        Schoolyr::create(['school_year' => $data['school_year']]);

        return back()->with('success', 'School year added.');
    }

    /* ==========================================================
     * Announcements (create/update/delete)
     * ========================================================== */
    public function storeAnnouncement(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'content'       => 'nullable|string',
            'date_of_event' => 'nullable|date',
            'deadline'      => 'nullable|date|after_or_equal:date_of_event',
            'gradelvl_id'   => 'nullable|exists:gradelvls,id',
        ]);

        Announcement::create($data);

        return back()->with('success', 'Announcement created.');
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'content'       => 'nullable|string',
            'date_of_event' => 'nullable|date',
            'deadline'      => 'nullable|date|after_or_equal:date_of_event',
            'gradelvl_id'   => 'nullable|exists:gradelvls,id',
        ]);

        $announcement->update($data);

        return back()->with('success', 'Announcement updated.');
    }

    public function destroyAnnouncement($id)
    {
        Announcement::findOrFail($id)->delete();
        return back()->with('success', 'Announcement deleted.');
    }

    /* ==========================================================
     * Schedules
     * ========================================================== */
    public function schedules()
    {
        $schedules = Schedule::with(['subject', 'room', 'section', 'gradelvl', 'faculty.user'])
            ->orderBy('day')
            ->get();

        $faculties = Faculty::with('user')->get();
        $subjects  = Subjects::all();
        $rooms     = Rooms::all();
        $sections  = Section::all();
        $gradelvls = Gradelvl::all();
        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get();

        return view('auth.admindashboard.schedules', compact(
            'schedules',
            'faculties',
            'subjects',
            'rooms',
            'sections',
            'gradelvls',
            'schoolyrs'
        ));
    }

    public function storeSchedule(Request $request)
    {
        $data = $request->validate([
            'day'         => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'class_start' => 'required|date_format:H:i',
            'class_end'   => 'required|date_format:H:i|after:class_start',
            'faculty_id'  => 'required|exists:faculties,id',
            'subject_id'  => 'required|exists:subjects,id',
            'room_id'     => 'required|exists:rooms,id',
            'section_id'  => 'nullable|exists:sections,id',
            'gradelvl_id' => 'nullable|exists:gradelvls,id',
            'school_year' => 'nullable|string|max:9|exists:schoolyrs,school_year',
        ]);

        // normalize "" → null so DB stores NULL for “— None —”
        $data['school_year'] = $data['school_year'] ?: null;

        Schedule::create($data);

        return back()->with('success', 'Schedule added successfully!');
    }

    public function updateSchedule(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $data = $request->validate([
            'day'         => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'class_start' => 'required|date_format:H:i',
            'class_end'   => 'required|date_format:H:i|after:class_start',
            'faculty_id'  => 'required|exists:faculties,id',
            'subject_id'  => 'required|exists:subjects,id',
            'room_id'     => 'required|exists:rooms,id',
            'section_id'  => 'nullable|exists:sections,id',
            'gradelvl_id' => 'nullable|exists:gradelvls,id',
            'school_year' => 'nullable|string|max:9|exists:schoolyrs,school_year',
        ]);

        $data['school_year'] = $data['school_year'] ?: null;

        $schedule->fill($data)->save();

        return back()->with('success', 'Schedule updated successfully!');
    }

    public function destroySchedule($id)
    {
        Schedule::findOrFail($id)->delete();
        return back()->with('success', 'Schedule deleted successfully!');
    }

    /* ==========================================================
     * Tuition (store/update/delete) — totals computed server-side
     * ========================================================== */
    public function storeTuition(Request $request)
    {
        $data = $request->validate([
            'grade_level'         => ['required', 'string', 'max:50'],
            'monthly_fee'         => ['nullable', 'numeric', 'min:0'],
            'yearly_fee'          => ['nullable', 'numeric', 'min:0'],
            'misc_fee'            => ['nullable', 'numeric', 'min:0'],
            'optional_fee_desc'   => ['nullable', 'string', 'max:255'],
            'optional_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'school_year'         => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
        ]);

        $months  = 10;
        $monthly = $data['monthly_fee'] ?? null;
        $yearly  = $data['yearly_fee']   ?? null;

        if (is_null($monthly) && is_null($yearly)) {
            return back()->withInput()->with('error', 'Enter either Monthly or School Year tuition.');
        }
        if (!is_null($monthly) && is_null($yearly)) {
            $yearly = $monthly * $months;
        } elseif (is_null($monthly) && !is_null($yearly)) {
            $monthly = $yearly / $months;
        }

        $miscForTotal = $request->filled('misc_fee') ? (float) $data['misc_fee'] : 0;
        $optForTotal  = $request->filled('optional_fee_amount') ? (float) $data['optional_fee_amount'] : 0;
        $totalYearly  = round($yearly + $miscForTotal + $optForTotal, 2);

        Tuition::create([
            'grade_level'         => $data['grade_level'],
            'monthly_fee'         => round($monthly, 2),
            'yearly_fee'          => round($yearly, 2),
            'misc_fee'            => $request->filled('misc_fee') ? round($data['misc_fee'], 2) : null,
            'optional_fee_desc'   => $data['optional_fee_desc'] ?? null,
            'optional_fee_amount' => $request->filled('optional_fee_amount') ? round($data['optional_fee_amount'], 2) : null,
            'total_yearly'        => $totalYearly,
            'school_year'         => $data['school_year'] ?? null,
        ]);

        return back()->with('success', 'Tuition saved.');
    }

    public function updateTuition(Request $request, $id)
    {
        $tuition = Tuition::findOrFail($id);

        $data = $request->validate([
            'grade_level'         => ['required', 'string', 'max:50'],
            'monthly_fee'         => ['nullable', 'numeric', 'min:0'],
            'yearly_fee'          => ['nullable', 'numeric', 'min:0'],
            'misc_fee'            => ['nullable', 'numeric', 'min:0'],
            'optional_fee_desc'   => ['nullable', 'string', 'max:255'],
            'optional_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'school_year'         => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
        ]);

        $months       = 10;
        $monthlyInput = $request->input('monthly_fee');
        $yearlyInput  = $request->input('yearly_fee');

        if (is_null($monthlyInput) && is_null($yearlyInput)) {
            $monthly = (float) $tuition->monthly_fee;
            $yearly  = (float) $tuition->yearly_fee;
        } elseif (!is_null($monthlyInput) && is_null($yearlyInput)) {
            $monthly = (float) $monthlyInput;
            $yearly  = $monthly * $months;
        } elseif (is_null($monthlyInput) && !is_null($yearlyInput)) {
            $yearly  = (float) $yearlyInput;
            $monthly = $yearly / $months;
        } else {
            $monthly = (float) $monthlyInput;
            $yearly  = $monthly * $months; // keep them in sync
        }

        $miscForTotal = $request->filled('misc_fee') ? (float) $data['misc_fee'] : 0;
        $optForTotal  = $request->filled('optional_fee_amount') ? (float) $data['optional_fee_amount'] : 0;
        $totalYearly  = round($yearly + $miscForTotal + $optForTotal, 2);

        $tuition->update([
            'grade_level'         => $data['grade_level'],
            'monthly_fee'         => round($monthly, 2),
            'yearly_fee'          => round($yearly, 2),
            'misc_fee'            => $request->filled('misc_fee') ? round($data['misc_fee'], 2) : null,
            'optional_fee_desc'   => $data['optional_fee_desc'] ?? null,
            'optional_fee_amount' => $request->filled('optional_fee_amount') ? round($data['optional_fee_amount'], 2) : null,
            'total_yearly'        => $totalYearly,
            'school_year'         => $data['school_year'] ?? null,
        ]);

        return back()->with('success', 'Tuition updated successfully!');
    }

    // Per-row Delete Tuition
    public function destroyTuition($id)
    {
        Tuition::findOrFail($id)->delete();
        return back()->with('success', 'Tuition deleted successfully!');
    }
}
