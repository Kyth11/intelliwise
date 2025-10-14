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
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function dashboard()
    {
        $students   = Student::with('guardian')->get();
        $faculties  = Faculty::with('user')->get();
        $guardians  = Guardian::with('user')->get();

        $announcements = Announcement::with('gradelvls')
            ->orderByRaw('COALESCE(date_of_event, created_at) DESC')
            ->take(50)
            ->get();

        $schedules = Schedule::with(['subject', 'gradelvl', 'faculty.user'])
            ->orderBy('day')
            ->get();

        $tuitions      = Tuition::with('optionalFees')->orderBy('updated_at', 'desc')->get();
        $subjects      = Subjects::all();
        $gradelvls     = Gradelvl::all();
        $schoolyrs     = Schoolyr::orderBy('school_year', 'desc')->get();
        $optionalFees  = OptionalFee::where('active', true)->orderBy('name')->get();

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

    public function students()
    {
        $students = Student::with('guardian')->get();
        return view('auth.admindashboard.students', compact('students'));
    }

    public function faculties()
    {
        $faculties = Faculty::with('user')->get();
        return view('auth.admindashboard.faculties', compact('faculties'));
    }

    public function accounts()
    {
        $faculties = Faculty::with('user')->get();
        $guardians = Guardian::with('user')->get();
        return view('auth.admindashboard.accounts', compact('faculties', 'guardians'));
    }

    public function settings()
    {
        $admins    = User::where('role', 'admin')->orderBy('created_at', 'desc')->get();
        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get();

        return view('auth.admindashboard.settings', compact('admins', 'schoolyrs'));
    }

    public function storeAdmin(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['in:admin'],
        ]);

        User::create([
            'name'        => $data['name'],
            'username'    => $data['username'],
            'password'    => Hash::make($data['password']),
            'role'        => 'admin',
            'faculty_id'  => null,
            'guardian_id' => null,
        ]);

        return back()->with('success', 'Admin account created.');
    }

    public function destroyAdmin($id)
    {
        $toDelete = User::where('id', $id)->where('role', 'admin')->firstOrFail();

        if (Auth::id() === $toDelete->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

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

    public function storeAnnouncement(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'content'       => 'nullable|string',
            'date_of_event' => 'nullable|date',
            'deadline'      => 'nullable|date|after_or_equal:date_of_event',
            'gradelvl_ids'  => 'array',
            'gradelvl_ids.*'=> 'nullable|exists:gradelvls,id',
        ]);

        $first = collect($data['gradelvl_ids'] ?? [])->filter()->first();

        $announcement = Announcement::create([
            'title'         => $data['title'],
            'content'       => $data['content'] ?? null,
            'date_of_event' => $data['date_of_event'] ?? null,
            'deadline'      => $data['deadline'] ?? null,
            'gradelvl_id'   => $first ?: null,
        ]);

        $ids = collect($data['gradelvl_ids'] ?? [])->filter()->unique()->values();
        $announcement->gradelvls()->sync($ids);

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
            'gradelvl_ids'  => 'array',
            'gradelvl_ids.*'=> 'nullable|exists:gradelvls,id',
        ]);

        $first = collect($data['gradelvl_ids'] ?? [])->filter()->first();

        $announcement->update([
            'title'         => $data['title'],
            'content'       => $data['content'] ?? null,
            'date_of_event' => $data['date_of_event'] ?? null,
            'deadline'      => $data['deadline'] ?? null,
            'gradelvl_id'   => $first ?: null,
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

    public function schedules()
    {
        $schedules = Schedule::with(['subject', 'gradelvl', 'faculty.user'])
            ->orderBy('day')
            ->get();

        $faculties = Faculty::with('user')->get();
        $subjects  = Subjects::all();
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
            'day'         => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'class_start' => 'required|date_format:H:i',
            'class_end'   => 'required|date_format:H:i|after:class_start',
            'faculty_id'  => 'required|exists:faculties,id',
            'subject_id'  => 'required|exists:subjects,id',
            'gradelvl_id' => 'nullable|exists:gradelvls,id',
            'school_year' => 'nullable|string|max:9|exists:schoolyrs,school_year',
        ]);

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
            'gradelvls_id'=> 'nullable|exists:gradelvls,id',
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

    public function storeTuition(Request $request)
    {
        $data = $request->validate([
            'grade_level'     => ['required', 'string', 'max:50'],
            'tuition_monthly' => ['nullable', 'numeric', 'min:0'],
            'tuition_yearly'  => ['nullable', 'numeric', 'min:0'],
            'misc_monthly'    => ['nullable', 'numeric', 'min:0'],
            'misc_yearly'     => ['nullable', 'numeric', 'min:0'],
            'books_desc'      => ['nullable', 'string', 'max:255'],
            'books_amount'    => ['nullable', 'numeric', 'min:0'],
            'optional_fee_ids'=> ['array'],
            'optional_fee_ids.*' => ['integer', 'exists:optional_fees,id'],
            'school_year'     => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
        ]);

        $months = 10;

        $tMon  = $data['tuition_monthly'] ?? null;
        $tYear = $data['tuition_yearly'] ?? null;
        if (is_null($tMon) && is_null($tYear)) {
            return back()->withInput()->with('error', 'Enter tuition monthly or yearly.');
        }
        if (is_null($tMon))  $tMon  = $tYear / $months;
        if (is_null($tYear)) $tYear = $tMon * $months;

        $mMon  = $data['misc_monthly'] ?? null;
        $mYear = $data['misc_yearly'] ?? null;
        if (!is_null($mMon) && is_null($mYear)) $mYear = $mMon * $months;
        if (is_null($mMon) && !is_null($mYear)) $mMon = $mYear / $months;

        $books = $request->filled('books_amount') ? (float) $data['books_amount'] : 0;

        $baseTotal = round(($tYear ?? 0) + ($mYear ?? 0) + ($books ?: 0), 2);

        $tuition = Tuition::create([
            'grade_level'    => $data['grade_level'],
            'tuition_monthly'=> round($tMon, 2),
            'tuition_yearly' => round($tYear, 2),
            'misc_monthly'   => $mMon !== null ? round($mMon, 2) : null,
            'misc_yearly'    => $mYear !== null ? round($mYear, 2) : null,
            'books_desc'     => $data['books_desc'] ?? null,
            'books_amount'   => $request->filled('books_amount') ? round($books, 2) : null,
            'school_year'    => $data['school_year'] ?? null,
            'total_yearly'   => 0,
        ]);

        $ids = collect($data['optional_fee_ids'] ?? [])->filter()->unique()->values();
        if ($ids->isNotEmpty()) $tuition->optionalFees()->sync($ids);

        $optSum = $tuition->optionalFees()->sum('amount');
        $tuition->update(['total_yearly' => round($baseTotal + $optSum, 2)]);

        return back()->with('success', 'Tuition saved.');
    }

    public function updateTuition(Request $request, $id)
    {
        $tuition = Tuition::findOrFail($id);

        $data = $request->validate([
            'grade_level'     => ['required', 'string', 'max:50'],
            'tuition_monthly' => ['nullable', 'numeric', 'min:0'],
            'tuition_yearly'  => ['nullable', 'numeric', 'min:0'],
            'misc_monthly'    => ['nullable', 'numeric', 'min:0'],
            'misc_yearly'     => ['nullable', 'numeric', 'min:0'],
            'books_desc'      => ['nullable', 'string', 'max:255'],
            'books_amount'    => ['nullable', 'numeric', 'min:0'],
            'optional_fee_ids'=> ['array'],
            'optional_fee_ids.*' => ['integer', 'exists:optional_fees,id'],
            'school_year'     => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
        ]);

        $months = 10;

        $tMon  = $request->input('tuition_monthly');
        $tYear = $request->input('tuition_yearly');
        if (is_null($tMon) && is_null($tYear)) {
            $tMon  = (float) $tuition->tuition_monthly;
            $tYear = (float) $tuition->tuition_yearly;
        } elseif (is_null($tMon)) {
            $tMon = $tYear / $months;
        } elseif (is_null($tYear)) {
            $tYear = $tMon * $months;
        }

        $mMon  = $request->input('misc_monthly');
        $mYear = $request->input('misc_yearly');
        if (is_null($mMon) && is_null($mYear)) {
            $mMon  = $tuition->misc_monthly;
            $mYear = $tuition->misc_yearly;
        } elseif (is_null($mMon)) {
            $mMon = $mYear / $months;
        } elseif (is_null($mYear)) {
            $mYear = $mMon * $months;
        }

        $books = $request->filled('books_amount')
            ? (float) $request->input('books_amount')
            : ($tuition->books_amount ?? 0);

        $tuition->update([
            'grade_level'    => $data['grade_level'],
            'tuition_monthly'=> round($tMon, 2),
            'tuition_yearly' => round($tYear, 2),
            'misc_monthly'   => $mMon !== null ? round($mMon, 2) : null,
            'misc_yearly'    => $mYear !== null ? round($mYear, 2) : null,
            'books_desc'     => $data['books_desc'] ?? null,
            'books_amount'   => $request->filled('books_amount') ? round($books, 2) : null,
            'school_year'    => $data['school_year'] ?? null,
        ]);

        $ids = collect($data['optional_fee_ids'] ?? [])->filter()->unique()->values();
        $tuition->optionalFees()->sync($ids);

        $baseTotal = round(($tYear ?? 0) + ($mYear ?? 0) + ($books ?: 0), 2);
        $optSum    = $tuition->optionalFees()->sum('amount');

        $tuition->update(['total_yearly' => round($baseTotal + $optSum, 2)]);

        return back()->with('success', 'Tuition updated successfully!');
    }

    public function destroyTuition($id)
    {
        Tuition::findOrFail($id)->delete();
        return back()->with('success', 'Tuition deleted successfully!');
    }

    public function storeOptionalFee(Request $request)
    {
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'scope'  => ['nullable', 'in:grade,student,both'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        OptionalFee::create([
            'name'   => $data['name'],
            'amount' => $data['amount'],
            'scope'  => $data['scope'] ?? 'both',
            'active' => $request->boolean('active'),
        ]);

        return back()->with('success', 'Optional fee added.');
    }

    public function updateOptionalFee(Request $request, $id)
    {
        $fee = OptionalFee::findOrFail($id);

        $data = $request->validate([
            'name'   => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'scope'  => ['nullable', 'in:grade,student,both'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        $fee->update([
            'name'   => $data['name'],
            'amount' => $data['amount'],
            'scope'  => $data['scope'] ?? $fee->scope,
            'active' => $request->boolean('active'),
        ]);

        return back()->with('success', 'Optional fee updated.');
    }

    public function destroyOptionalFee($id)
    {
        $fee = OptionalFee::findOrFail($id);
        $fee->tuitions()->detach();
        $fee->students()->detach();
        $fee->delete();

        return back()->with('success', 'Optional fee deleted.');
    }

    public function finances()
    {
        $tuitions     = Tuition::with('optionalFees')->orderBy('grade_level')->get();
        $optionalFees = OptionalFee::orderBy('name')->get();
        $schoolyrs    = Schoolyr::orderBy('school_year', 'desc')->get();
        $students     = Student::with('guardian')->get();
        $guardians    = Guardian::with('students')->get()->map(function ($g) {
            $mother = trim(collect([$g->m_firstname, $g->m_middlename, $g->m_lastname])->filter()->implode(' '));
            $father = trim(collect([$g->f_firstname, $g->f_middlename, $g->f_lastname])->filter()->implode(' '));
            $label  = $mother && $father ? ($mother . ' & ' . $father) : ($mother ?: ($father ?: 'Guardian #'.$g->id));
            $g->display_name = $label;
            return $g;
        });

        return view('auth.admindashboard.finances', compact(
            'tuitions',
            'optionalFees',
            'schoolyrs',
            'students',
            'guardians'
        ));
    }
}
