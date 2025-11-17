<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AppSetting;
use App\Models\Faculty;
use App\Models\Gradelvl;
use App\Models\Guardian;
use App\Models\OptionalFee;
use App\Models\Payments;
use App\Models\Schedule;
use App\Models\Schoolyr;
use App\Models\Student;
use App\Models\Subjects;
use App\Models\Tuition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Admin landing dashboard (read-only data aggregates).
     */
    public function dashboard()
    {
        $students = Student::with([
            'guardian',
            'optionalFees',
            'tuition',
            'gradelvl',
        ])->get();

        $faculties = Faculty::with('user')->get();
        $guardians = Guardian::with('user')->get();

        $announcements = Announcement::with('gradelvls')
            ->orderByRaw('COALESCE(date_of_event, created_at) DESC')
            ->take(50)
            ->get();

        $schedules = Schedule::with(['subject', 'gradelvl', 'faculty.user'])
            ->orderBy('day')
            ->get();

        $tuitions     = Tuition::with('optionalFees')->orderBy('updated_at', 'desc')->get();
        $subjects     = Subjects::with('gradelvl')->orderBy('subject_name')->get();
        $gradelvls    = Gradelvl::orderBy('grade_level')->get();
        $schoolyrs    = Schoolyr::orderBy('school_year', 'desc')->get();
        $optionalFees = OptionalFee::where('active', true)->orderBy('name')->get();

        $recentPayments = Payments::with('tuition')
            ->latest('created_at')
            ->take(10)
            ->get();

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
            'optionalFees',
            'recentPayments'
        ));
    }

    /**
     * Accounts page (lists faculty + guardians).
     */
    public function accounts()
    {
        $faculties = Faculty::with('user')->get();
        $guardians = Guardian::with('user')->get();

        return view('auth.admindashboard.accounts', compact('faculties', 'guardians'));
    }

    /**
     * Settings page (view only).
     * CRUD actions are handled by Admin\SettingController.
     */
    public function settings()
    {
        $admins    = User::where('role', 'admin')->orderBy('created_at', 'desc')->get();
        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get();

        $subjects  = Subjects::with('gradelvl')->orderBy('subject_name')->get();
        $gradelvls = Gradelvl::orderBy('grade_level')->get(['id', 'grade_level']);

        $facultyEnrollmentEnabled = (bool) AppSetting::get('faculty_enrollment_enabled', true);

        return view('auth.admindashboard.settings', compact(
            'admins',
            'schoolyrs',
            'subjects',
            'gradelvls',
            'facultyEnrollmentEnabled'
        ));
    }

    /**
     * Schedules page (view only). CRUD in Admin\ScheduleController.
     */
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

    /**
     * Finances page (view only). Tuition/Optional Fee CRUD in Admin\* controllers.
     */
    public function finances()
    {
        $tuitions     = Tuition::with('optionalFees')->orderBy('grade_level')->get();
        $optionalFees = OptionalFee::orderBy('name')->get();
        $schoolyrs    = Schoolyr::orderBy('school_year', 'desc')->get();
        $students     = Student::with('guardian')->get();

        $guardians = Guardian::with('students')->get()->map(function ($g) {
            $mother = trim(collect([$g->m_firstname, $g->m_middlename, $g->m_lastname])->filter()->implode(' '));
            $father = trim(collect([$g->f_firstname, $g->f_middlename, $g->f_lastname])->filter()->implode(' '));
            $label  = $mother && $father ? ($mother . ' & ' . $father) : ($mother ?: ($father ?: 'Guardian #' . $g->id));
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


    /**
     * Settings page (view only).
     * CRUD actions are handled by Admin\SettingController.
     */
    public function curriculum()
    {
        $admins    = User::where('role', 'admin')->orderBy('created_at', 'desc')->get();
        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get(['id', 'school_year']);

        $subjects  = Subjects::orderBy('subject_name')->get();
        $gradelvls = Gradelvl::get(['id', 'grade_level']);

        $child = false;
        $faculties = Faculty::with('user')->get();


        $result = DB::select(
            "select c.*,
            g.grade_level,
            sy.school_year,
            f.`name`
            from curriculum c 
            left join gradelvls g ON g.id = c.grade_id 
            left join schoolyrs sy ON sy.id = c.schoolyr_id
            left join users f ON f.faculty_id = c.adviser_id where c.deleted = ?",
            [0]
        );



        return view('auth.admindashboard.curriculum', compact(
            'admins',
            'schoolyrs',
            'subjects',
            'gradelvls',
            'child',
            'faculties',
            'result'
        ));
    }

    public function curriculum_edit($id){
         $admins    = User::where('role', 'admin')->orderBy('created_at', 'desc')->get();
        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get(['id', 'school_year']);

        $subjects  = Subjects::orderBy('subject_name')->get();
        $gradelvls = Gradelvl::get(['id', 'grade_level']);

        $child = DB::select(
            "select c.*,
            s.subject_name,
            s.subject_code
            from curriculum_child c 
            left join subjects s ON s.id = c.subject_id
            where c.deleted = ? and c.curriculum_id = ?",
            [0, $id]
        );
        $faculties = Faculty::with('user')->get();


        $result = DB::select(
            "select c.*,
            g.grade_level,
            sy.school_year,
            f.`name`
            from curriculum c 
            left join gradelvls g ON g.id = c.grade_id 
            left join schoolyrs sy ON sy.id = c.schoolyr_id
            left join users f ON f.faculty_id = c.adviser_id where c.deleted = ? and c.id = ?",
            [0, $id]
        )[0];


        return view('auth.admindashboard.partials.edit-curriculum', compact(
            'admins',
            'schoolyrs',
            'subjects',
            'gradelvls',
            'child',
            'faculties',
            'result'
        ));
    }
}
