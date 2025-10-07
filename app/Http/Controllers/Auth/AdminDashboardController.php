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
use App\Models\OptionalFee;
use App\Models\Subjects;
use App\Models\Gradelvl;
use App\Models\Schoolyr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; // ✅ use Facade for IDE-friendly Auth::id()

class AdminDashboardController extends Controller
{
    /**
     * Show admin dashboard with all necessary data for modals and lists.
     */
    public function dashboard()
    {
        $students = Student::with('guardian')->get();
        $faculties = Faculty::with('user')->get();
        $guardians = Guardian::with('user')->get();

        // Load grades pivot for display/use
        $announcements = Announcement::with('gradelvls')
            ->orderByRaw('COALESCE(date_of_event, created_at) DESC')
            ->take(50)
            ->get();

        $schedules = Schedule::with(['subject', 'gradelvl', 'faculty.user'])
            ->orderBy('day')
            ->get();

        // Load ALL tuition rows (with grade-level optional fees eager-loaded)
        $tuitions = Tuition::with('optionalFees')->orderBy('updated_at', 'desc')->get();

        // Lists for modals
        $subjects = Subjects::all();
        $gradelvls = Gradelvl::all(); // used by announcements & schedules forms
        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get();

        // Only active optional fees shown by default in UI
        $optionalFees = OptionalFee::where('active', true)->orderBy('name')->get();

        return view('auth.admindashboard', compact(
            'students',
            'faculties',
            'guardians',
            'announcements',
            'schedules',
            'tuitions',
            'subjects',
            'gradelvls',
            'schoolyrs',
            'optionalFees'
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
        $admins = User::where('role', 'admin')->orderBy('created_at', 'desc')->get();
        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get();

        return view('auth.admindashboard.settings', compact('admins', 'schoolyrs'));
    }

    public function storeAdmin(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['in:admin'], // locked to admin
        ]);

        User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
            'faculty_id' => null,
            'guardian_id' => null,
        ]);

        return back()->with('success', 'Admin account created.');
    }

    public function destroyAdmin($id)
    {
        $toDelete = User::where('id', $id)->where('role', 'admin')->firstOrFail();

        // Prevent deleting self (use Facade so IDEs don't underline)
        if (Auth::id() === $toDelete->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting the last remaining admin
        $adminCount = User::where('role', 'admin')->count();
        if ($adminCount <= 1) {
            return back()->with('error', 'Cannot delete the last admin account.');
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
     * Announcements (create/update/delete) — MULTI-GRADE SUPPORT
     * ========================================================== */
    public function storeAnnouncement(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'date_of_event' => 'nullable|date',
            'deadline' => 'nullable|date|after_or_equal:date_of_event',
            'gradelvl_ids' => 'array',
            'gradelvl_ids.*' => 'nullable|exists:gradelvls,id',
        ]);

        // Keep single FK for legacy UIs: first selected grade (or null = all)
        $first = collect($data['gradelvl_ids'] ?? [])->filter()->first();

        $announcement = Announcement::create([
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'date_of_event' => $data['date_of_event'] ?? null,
            'deadline' => $data['deadline'] ?? null,
            'gradelvl_id' => $first ?: null,   // legacy/compat
        ]);

        // Sync pivot with all selected grades
        $ids = collect($data['gradelvl_ids'] ?? [])->filter()->unique()->values();
        $announcement->gradelvls()->sync($ids);

        return back()->with('success', 'Announcement created.');
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'date_of_event' => 'nullable|date',
            'deadline' => 'nullable|date|after_or_equal:date_of_event',
            'gradelvl_ids' => 'array',
            'gradelvl_ids.*' => 'nullable|exists:gradelvls,id',
        ]);

        $first = collect($data['gradelvl_ids'] ?? [])->filter()->first();

        $announcement->update([
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'date_of_event' => $data['date_of_event'] ?? null,
            'deadline' => $data['deadline'] ?? null,
            'gradelvl_id' => $first ?: null,   // keep legacy field in sync
        ]);

        $ids = collect($data['gradelvl_ids'] ?? [])->filter()->unique()->values();
        $announcement->gradelvls()->sync($ids);

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
        $schedules = Schedule::with(['subject', 'gradelvl', 'faculty.user'])
            ->orderBy('day')
            ->get();

        $faculties = Faculty::with('user')->get();
        $subjects = Subjects::all();
        $gradelvls = Gradelvl::all();
        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get();

        return view('auth.admindashboard.schedules', compact(
            'schedules',
            'faculties',
            'subjects',
            'gradelvls',
            'schoolyrs'
        ));
    }

    public function storeSchedule(Request $request)
    {
        $data = $request->validate([
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'class_start' => 'required|date_format:H:i',
            'class_end' => 'required|date_format:H:i|after:class_start',
            'faculty_id' => 'required|exists:faculties,id',
            'subject_id' => 'required|exists:subjects,id',
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
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'class_start' => 'required|date_format:H:i',
            'class_end' => 'required|date_format:H:i|after:class_start',
            'faculty_id' => 'required|exists:faculties,id',
            'subject_id' => 'required|exists:subjects,id',
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
     * Tuition (store/update/delete) — split tuition/fees + grade-level optional
     * ========================================================== */
    public function storeTuition(Request $request)
    {
        $data = $request->validate([
            'grade_level' => ['required', 'string', 'max:50'],

            // tuition (enter monthly or yearly; we compute the other)
            'tuition_monthly' => ['nullable', 'numeric', 'min:0'],
            'tuition_yearly' => ['nullable', 'numeric', 'min:0'],

            // fees (enter monthly or yearly; we compute the other)
            'misc_monthly' => ['nullable', 'numeric', 'min:0'],
            'misc_yearly' => ['nullable', 'numeric', 'min:0'],

            'books_desc' => ['nullable', 'string', 'max:255'],
            'books_amount' => ['nullable', 'numeric', 'min:0'],

            // attach grade-level optional fees
            'optional_fee_ids' => ['array'],
            'optional_fee_ids.*' => ['integer', 'exists:optional_fees,id'],

            // keep as text "YYYY-YYYY" (your views expect this)
            'school_year' => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
        ]);

        $months = 10;

        // Normalize tuition
        $tMon = $data['tuition_monthly'] ?? null;
        $tYear = $data['tuition_yearly'] ?? null;
        if (is_null($tMon) && is_null($tYear)) {
            return back()->withInput()->with('error', 'Enter tuition monthly or yearly.');
        }
        if (is_null($tMon)) {
            $tMon = $tYear / $months;
        }
        if (is_null($tYear)) {
            $tYear = $tMon * $months;
        }

        // Normalize misc
        $mMon = $data['misc_monthly'] ?? null;
        $mYear = $data['misc_yearly'] ?? null;
        if (!is_null($mMon) && is_null($mYear)) {
            $mYear = $mMon * $months;
        }
        if (is_null($mMon) && !is_null($mYear)) {
            $mMon = $mYear / $months;
        }

        $books = $request->filled('books_amount') ? (float) $data['books_amount'] : 0;

        // Base total
        $baseTotal = round(($tYear ?? 0) + ($mYear ?? 0) + ($books ?: 0), 2);

        // Create tuition
        $tuition = Tuition::create([
            'grade_level' => $data['grade_level'],
            'tuition_monthly' => round($tMon, 2),
            'tuition_yearly' => round($tYear, 2),
            'misc_monthly' => $mMon !== null ? round($mMon, 2) : null,
            'misc_yearly' => $mYear !== null ? round($mYear, 2) : null,
            'books_desc' => $data['books_desc'] ?? null,
            'books_amount' => $request->filled('books_amount') ? round($books, 2) : null,
            'school_year' => $data['school_year'] ?? null,
            'total_yearly' => 0, // temp; update after optional fees attached
        ]);

        // Attach grade-level optional fees
        $ids = collect($data['optional_fee_ids'] ?? [])->filter()->unique()->values();
        if ($ids->isNotEmpty()) {
            $tuition->optionalFees()->sync($ids);
        }

        // Update total_yearly with grade-level optional sum
        $optSum = $tuition->optionalFees()->sum('amount');
        $tuition->update([
            'total_yearly' => round($baseTotal + $optSum, 2)
        ]);

        return back()->with('success', 'Tuition saved.');
    }

    public function updateTuition(Request $request, $id)
    {
        $tuition = Tuition::findOrFail($id);

        $data = $request->validate([
            'grade_level' => ['required', 'string', 'max:50'],

            'tuition_monthly' => ['nullable', 'numeric', 'min:0'],
            'tuition_yearly' => ['nullable', 'numeric', 'min:0'],

            'misc_monthly' => ['nullable', 'numeric', 'min:0'],
            'misc_yearly' => ['nullable', 'numeric', 'min:0'],

            'books_desc' => ['nullable', 'string', 'max:255'],
            'books_amount' => ['nullable', 'numeric', 'min:0'],

            'optional_fee_ids' => ['array'],
            'optional_fee_ids.*' => ['integer', 'exists:optional_fees,id'],

            'school_year' => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
        ]);

        $months = 10;

        // Tuition
        $tMon = $request->input('tuition_monthly');
        $tYear = $request->input('tuition_yearly');
        if (is_null($tMon) && is_null($tYear)) {
            $tMon = (float) $tuition->tuition_monthly;
            $tYear = (float) $tuition->tuition_yearly;
        } elseif (is_null($tMon)) {
            $tMon = $tYear / $months;
        } elseif (is_null($tYear)) {
            $tYear = $tMon * $months;
        }

        // Misc
        $mMon = $request->input('misc_monthly');
        $mYear = $request->input('misc_yearly');
        if (is_null($mMon) && is_null($mYear)) {
            $mMon = $tuition->misc_monthly;
            $mYear = $tuition->misc_yearly;
        } elseif (is_null($mMon)) {
            $mMon = $mYear / $months;
        } elseif (is_null($mYear)) {
            $mYear = $mMon * $months;
        }

        $books = $request->filled('books_amount')
            ? (float) $request->input('books_amount')
            : ($tuition->books_amount ?? 0);

        // Update core
        $tuition->update([
            'grade_level' => $data['grade_level'],
            'tuition_monthly' => round($tMon, 2),
            'tuition_yearly' => round($tYear, 2),
            'misc_monthly' => $mMon !== null ? round($mMon, 2) : null,
            'misc_yearly' => $mYear !== null ? round($mYear, 2) : null,
            'books_desc' => $data['books_desc'] ?? null,
            'books_amount' => $request->filled('books_amount') ? round($books, 2) : null,
            'school_year' => $data['school_year'] ?? null,
        ]);

        // Sync grade-level optional fees
        $ids = collect($data['optional_fee_ids'] ?? [])->filter()->unique()->values();
        $tuition->optionalFees()->sync($ids);

        // Recompute total
        $baseTotal = round(($tYear ?? 0) + ($mYear ?? 0) + ($books ?: 0), 2);
        $optSum = $tuition->optionalFees()->sum('amount');

        $tuition->update([
            'total_yearly' => round($baseTotal + $optSum, 2)
        ]);

        return back()->with('success', 'Tuition updated successfully!');
    }

    // Per-row Delete Tuition
    public function destroyTuition($id)
    {
        Tuition::findOrFail($id)->delete();
        return back()->with('success', 'Tuition deleted successfully!');
    }

    /* ==========================================================
     * OPTIONAL FEES (in AdminDashboardController)
     * ========================================================== */
    // OPTIONAL FEES
    public function storeOptionalFee(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'scope' => ['nullable', 'in:grade,student,both'],
            // we’ll coerce active below; still allow 0/1 to pass
            'active' => ['nullable', 'in:0,1'],
        ]);

        OptionalFee::create([
            'name' => $data['name'],
            'amount' => $data['amount'],
            'scope' => $data['scope'] ?? 'both',
            // Use boolean() so any truthy value becomes 1
            'active' => $request->boolean('active'),
        ]);

        return back()->with('success', 'Optional fee added.');
    }

    public function updateOptionalFee(Request $request, $id)
    {
        $fee = OptionalFee::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'scope' => ['nullable', 'in:grade,student,both'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        $fee->update([
            'name' => $data['name'],
            'amount' => $data['amount'],
            'scope' => $data['scope'] ?? $fee->scope,
            'active' => $request->boolean('active'), // coerce "1"/"0" to bool
        ]);

        return back()->with('success', 'Optional fee updated.');
    }

    public function destroyOptionalFee($id)
    {
        $fee = OptionalFee::findOrFail($id);

        // Not strictly necessary due to cascadeOnDelete in migrations, but safe to detach first
        $fee->tuitions()->detach();
        $fee->students()->detach();

        $fee->delete();

        return back()->with('success', 'Optional fee deleted.');
    }

    public function finances()
    {
        // Ensure relations exist: Tuition has relation optionalFees()
        $tuitions      = Tuition::with('optionalFees')->orderBy('grade_level')->get();
        $optionalFees  = OptionalFee::orderBy('name')->get();
        $schoolyrs     = Schoolyr::orderBy('school_year', 'desc')->get();

        return view('auth.admindashboard.finances', compact('tuitions', 'optionalFees', 'schoolyrs'));
    }
}
