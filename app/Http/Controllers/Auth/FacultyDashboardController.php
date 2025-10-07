<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Faculty;
use App\Models\Schedule;
use App\Models\Subjects;
use App\Models\Gradelvl;
use App\Models\Student;
use App\Models\Announcement;
use App\Models\Guardian;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FacultyDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $faculties = Faculty::with([
                'user',
                'schedules.subject',
                'schedules.gradelvl', // singular (matches model)
            ])->get();

            $subjects  = Subjects::all();
            $gradelvls = Gradelvl::all();

            return view('auth.admindashboard.faculties', compact(
                'faculties',
                'subjects',
                'gradelvls'
            ));
        }

        $faculty = Faculty::with(['user'])->find($user->faculty_id);

        $canSeeAllStudents      = ($user->username === 'faculty');
        $canSeeAllAnnouncements = $canSeeAllStudents;
        $canEnroll              = ($user->role === 'faculty') || ($user->role === 'admin');

        $mySchedule = Schedule::where('faculty_id', $user->faculty_id)
            ->with('gradelvl:id,grade_level')
            ->get();

        $myGradelvlIds   = $mySchedule->pluck('gradelvl.id')->filter()->unique()->values();
        $myGradelvlNames = $mySchedule->pluck('gradelvl.grade_level')->filter()->unique()->values();

        $studentsCount = $canSeeAllStudents
            ? Student::count()
            : Student::whereIn('s_gradelvl', $myGradelvlNames)->count();

        $announcements = $this->queryAnnouncementsForFaculty($canSeeAllAnnouncements, $myGradelvlIds)
            ->latest()
            ->get();

        $assignmentsCount = 0;

        $gradelvls = $canSeeAllAnnouncements
            ? Gradelvl::orderBy('grade_level')->get(['id', 'grade_level'])
            : Gradelvl::whereIn('id', $myGradelvlIds)->orderBy('grade_level')->get(['id', 'grade_level']);

        return view('auth.facultydashboard', compact(
            'faculty',
            'studentsCount',
            'assignmentsCount',
            'announcements',
            'gradelvls',
            'canSeeAllStudents',
            'canSeeAllAnnouncements',
            'canEnroll'
        ));
    }

    public function schedule()
    {
        $user = Auth::user();

        $query = Schedule::with(['subject', 'gradelvl', 'faculty']);
        $canSeeAll = ($user->username === 'faculty');

        if ($user->role === 'faculty' && !$canSeeAll) {
            $query->where('faculty_id', $user->faculty_id);
        }

        $query->orderByRaw("FIELD(day, 'Mon','Tue','Wed','Thu','Fri','Sat','Sun')")
              ->orderBy('class_start');

        $schedules = $query->get();

        return view('auth.facultydashboard.schedule', [
            'schedules' => $schedules,
            'canSeeAll' => $canSeeAll
        ]);
    }

    /** ===== Faculty Announcements CRUD (scoped) ===== */
    public function storeAnnouncement(Request $request)
    {
        $user = Auth::user();
        $canSeeAll = ($user->username === 'faculty');

        $allowedGradelvlIds = $canSeeAll
            ? Gradelvl::pluck('id')
            : $this->myGradelvlIds($user->faculty_id);

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'content'        => 'nullable|string',
            'date_of_event'  => 'nullable|date',
            'deadline'       => 'nullable|date',
            'gradelvl_ids'   => 'array',
            'gradelvl_ids.*' => 'integer|exists:gradelvls,id',
        ]);

        $selected = collect($validated['gradelvl_ids'] ?? []);
        if ($selected->isNotEmpty() && !$canSeeAll) {
            $selected = $selected->intersect($allowedGradelvlIds);
        }

        $a = new Announcement();
        $a->title         = $validated['title'];
        $a->content       = $validated['content'] ?? null;
        $a->date_of_event = $validated['date_of_event'] ?? null;
        $a->deadline      = $validated['deadline'] ?? null;
        $a->save();

        if ($selected->isNotEmpty()) {
            $a->gradelvls()->sync($selected->all());
        }

        return back()->with('success', 'Announcement created.');
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $user = Auth::user();
        $canSeeAll = ($user->username === 'faculty');

        $a = Announcement::with('gradelvls')->findOrFail($id);

        $allowedGradelvlIds = $canSeeAll
            ? Gradelvl::pluck('id')
            : $this->myGradelvlIds($user->faculty_id);

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'content'        => 'nullable|string',
            'date_of_event'  => 'nullable|date',
            'deadline'       => 'nullable|date',
            'gradelvl_ids'   => 'array',
            'gradelvl_ids.*' => 'integer|exists:gradelvls,id',
        ]);

        $a->update([
            'title'         => $validated['title'],
            'content'       => $validated['content'] ?? null,
            'date_of_event' => $validated['date_of_event'] ?? null,
            'deadline'      => $validated['deadline'] ?? null,
        ]);

        $selected = collect($validated['gradelvl_ids'] ?? []);
        if ($selected->isNotEmpty() && !$canSeeAll) {
            $selected = $selected->intersect($allowedGradelvlIds);
        }

        if ($selected->isNotEmpty()) {
            $a->gradelvls()->sync($selected->all());
        } else {
            $a->gradelvls()->detach();
        }

        return back()->with('success', 'Announcement updated.');
    }

    public function destroyAnnouncement($id)
    {
        $user = Auth::user();
        $canSeeAll = ($user->username === 'faculty');

        $a = Announcement::with('gradelvls')->findOrFail($id);

        if (!$canSeeAll) {
            $myIds   = $this->myGradelvlIds($user->faculty_id); // fixed typo
            $isGlobal= $a->gradelvls->isEmpty();
            $overlap = $a->gradelvls->pluck('id')->intersect($myIds);
            if (!$isGlobal && $overlap->isEmpty()) {
                abort(403, 'You are not allowed to delete this announcement.');
            }
        }

        $a->gradelvls()->detach();
        $a->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    /** Helper: announcements visible to a given faculty */
    protected function queryAnnouncementsForFaculty(bool $canSeeAll, $myGradelvlIds)
    {
        $base = Announcement::with(['gradelvls']);

        if ($canSeeAll) {
            return $base;
        }

        return $base->where(function ($q) use ($myGradelvlIds) {
            $q->whereDoesntHave('gradelvls')
              ->orWhereHas('gradelvls', function ($qq) use ($myGradelvlIds) {
                  $qq->whereIn('gradelvls.id', $myGradelvlIds);
              });
        });
    }

    /** Helper: list of grade level IDs taught by a faculty */
    protected function myGradelvlIds($facultyId)
    {
        return Schedule::where('faculty_id', $facultyId)
            ->pluck('gradelvl_id')
            ->filter()
            ->unique()
            ->values();
    }

    /** Faculty Enroll Form */
    public function enrollForm()
    {
        $guardians = Guardian::orderBy('id', 'desc')->get()->map(function ($g) {
            $mother = trim(collect([$g->m_firstname, $g->m_middlename, $g->m_lastname])->filter()->implode(' '));
            $father = trim(collect([$g->f_firstname, $g->f_middlename, $g->f_lastname])->filter()->implode(' '));
            $label  = $mother && $father ? ($mother.' & '.$father)
                    : ($mother ?: ($father ?: 'Guardian #'.$g->id));

            $g->display_name    = $label;
            $g->display_contact = $g->g_contact ?: ($g->m_contact ?: ($g->f_contact ?: ''));
            return $g;
        });

        return view('auth.facultydashboard.enroll-student', compact('guardians'));
    }

    /** Admin creates a new faculty */
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

    public function edit($id)
    {
        $faculty = Faculty::with(['user', 'schedules'])->findOrFail($id);
        return view('auth.editfaculty', compact('faculty'));
    }

    /** Admin or self (by id) */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

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

    /** ✅ Self-update (no id in route) */
    public function updateSelf(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'faculty') {
            abort(403, 'Unauthorized');
        }

        // Load/create the faculty record tied to this user
        $faculty = $user->faculty_id ? Faculty::find($user->faculty_id) : null;
        if (!$faculty) {
            $faculty = new Faculty();
        }

        $request->validate([
            'f_firstname' => 'required|string|max:255',
            'f_lastname'  => 'required|string|max:255',
            'f_email'     => 'nullable|email|max:255|unique:faculties,f_email,' . ($faculty->id ?? 'NULL'),
            'username'    => 'required|string|max:255|unique:users,username,' . $user->id,
            'password'    => 'nullable|string|min:6',
        ]);

        // Save / update faculty
        $faculty->f_firstname  = $request->f_firstname;
        $faculty->f_middlename = $request->f_middlename;
        $faculty->f_lastname   = $request->f_lastname;
        $faculty->f_address    = $request->f_address;
        $faculty->f_contact    = $request->f_contact;
        $faculty->f_email      = $request->f_email;
        $faculty->save();

        // Ensure the user is linked
        if (!$user->faculty_id) {
            $user->faculty_id = $faculty->id;
        }

        // Update user account
        $user->name     = trim($faculty->f_firstname . ' ' . $faculty->f_lastname);
        $user->username = $request->username;
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }
        $user->save();

        return back()->with('success', 'Profile saved.');
    }

    public function updateSchedule(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $request->validate([
            'subject_id'  => 'required|exists:subjects,id',
            'gradelvl_id' => 'required|exists:gradelvls,id',
            'day'         => 'required|string|max:50',
            'class_start' => 'required|date_format:H:i',
            'class_end'   => 'required|date_format:H:i|after:class_start',
        ]);

        $schedule->update([
            'subject_id'  => $request->subject_id,
            'gradelvl_id' => $request->gradelvl_id,
            'day'         => $request->day,
            'class_start' => $request->class_start,
            'class_end'   => $request->class_end,
        ]);

        return back()->with('success', 'Schedule updated successfully!');
    }

    public function destroy($id)
    {
        $faculty = Faculty::findOrFail($id);
        User::where('faculty_id', $faculty->id)->delete();
        $faculty->delete();

        return back()->with('success', 'Faculty account deleted successfully!');
    }

    public function students()
    {
        $user = Auth::user();
        $canSeeAll = ($user->username === 'faculty');

        if ($canSeeAll) {
            $students = Student::with('guardian')
                ->orderBy('s_gradelvl')
                ->orderBy('s_lastname')
                ->get();
        } else {
            $gradeNames = Schedule::where('faculty_id', $user->faculty_id)
                ->with('gradelvl')
                ->get()
                ->pluck('gradelvl.grade_level')
                ->filter()
                ->unique()
                ->values();

            $students = Student::with('guardian')
                ->whereIn('s_gradelvl', $gradeNames)
                ->orderBy('s_gradelvl')
                ->orderBy('s_lastname')
                ->get();
        }

        $studentsByGrade = $students->groupBy('s_gradelvl')->sortKeys();

        return view('auth.facultydashboard.students', [
            'canSeeAll'       => $canSeeAll,
            'studentsByGrade' => $studentsByGrade,
        ]);
    }
}
