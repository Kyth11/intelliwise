<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Schedule;
use App\Models\Subjects;
use App\Models\Gradelvl;
use App\Models\Student;
use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

class FacultyDashboardController extends Controller
{
    /**
     * Faculty dashboard (view-only). Admin management moved to Admin\FacultyController.
     */
    public function index()
    {
        $user = Auth::user();

        // "Default faculty" (username === 'faculty') can see all students/announcements
        $canSeeAllStudents      = ($user->username === 'faculty');
        $canSeeAllAnnouncements = $canSeeAllStudents;
        $canEnroll              = ($user->role === 'faculty') || ($user->role === 'admin');

        $faculty = Faculty::with(['user'])->find($user->faculty_id);

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
            : Gradelvl::whereIn('id', $myGradelvls = $myGradelvlIds)->orderBy('grade_level')->get(['id', 'grade_level']);

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

    /**
     * Read-only schedule page for faculty.
     */
    public function schedule()
    {
        $user = Auth::user();

        $query = Schedule::with(['subject', 'gradelvl', 'faculty']);
        $canSeeAll = ($user->username === 'faculty');

        if ($user->role === 'faculty' && !$canSeeAll) {
            $query->where('faculty_id', $user->faculty_id);
        }

        $query->orderByRaw("FIELD(day, 'Monday','Tuesday','Wednesday','Thursday','Friday')")
              ->orderBy('class_start');

        $schedules = $query->get();

        return view('auth.facultydashboard.schedule', [
            'schedules' => $schedules,
            'canSeeAll' => $canSeeAll
        ]);
    }

    /**
     * Read-only students list for faculty.
     */
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

    /** ===== Internal helpers ===== */
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
}
