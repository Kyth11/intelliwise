<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Subjects;
use App\Models\Gradelvl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PDO;

class FacultyController extends Controller
{
    public function index()
    {
        $faculties = Faculty::with(['user'])->get();

        $subjects  = Subjects::all();
        $gradelvls = Gradelvl::all();

        
        $result = DB::select(
            "select c.*,
            g.grade_level,
            sy.school_year,
            f.`name` as faculty_name,
            CONCAT(sy.school_year, ' - ', g.grade_level) as `name`
            from curriculum c 
            left join gradelvls g ON g.id = c.grade_id 
            left join schoolyrs sy ON sy.id = c.schoolyr_id
            left join users f ON f.faculty_id = c.adviser_id where c.deleted = ?",
            [0]
        );
        return view('auth.admindashboard.faculties', compact(
            'faculties',
            'subjects',
            'gradelvls',
            'result'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'f_firstname' => 'required|string|max:255',
            'f_lastname'  => 'required|string|max:255',
            'f_email'     => 'nullable|email|max:255|unique:faculties,f_email',
            'f_address'   => 'nullable|string|max:255',
            'f_contact'   => 'nullable|string|max:255',

            'username'    => 'required|string|max:255|unique:users,username',
            'password'    => 'required|string|min:6',
        ]);

        DB::transaction(function () use ($request) {
            $faculty = Faculty::create([
                'f_firstname'  => $request->f_firstname,
                'f_middlename' => $request->f_middlename,
                'f_lastname'   => $request->f_lastname,
                'f_address'    => $request->f_address,
                'f_contact'    => $request->f_contact,
                'f_email'      => $request->f_email,
            ]);

            User::create([
                'name'       => $faculty->full_name ?: ($faculty->f_firstname.' '.$faculty->f_lastname),
                'username'   => $request->username,
                'password'   => bcrypt($request->password),
                'role'       => 'faculty',
                'faculty_id' => $faculty->id,
            ]);
        });

        return back()->with('success', 'Faculty account created successfully!');
    }

    public function update(Request $request, $id)
    {
        $faculty = Faculty::with('user')->findOrFail($id);
        $currentUserId = optional($faculty->user)->id;

        $request->validate([
            'f_firstname' => 'required|string|max:255',
            'f_lastname'  => 'required|string|max:255',
            'f_email'     => 'nullable|email|max:255|unique:faculties,f_email,' . $faculty->id,
            'f_address'   => 'nullable|string|max:255',
            'f_contact'   => 'nullable|string|max:255',

            'username'    => [
                'required','string','max:255',
                Rule::unique('users','username')->ignore($currentUserId),
            ],
            'password'    => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($request, $faculty, $currentUserId) {
            $faculty->update([
                'f_firstname'  => $request->f_firstname,
                'f_middlename' => $request->f_middlename,
                'f_lastname'   => $request->f_lastname,
                'f_address'    => $request->f_address,
                'f_contact'    => $request->f_contact,
                'f_email'      => $request->f_email,
            ]);

            $name = $faculty->full_name ?: ($faculty->f_firstname.' '.$faculty->f_lastname);

            if ($faculty->user) {
                $payload = [
                    'name'     => $name,
                    'username' => $request->username,
                ];
                if ($request->filled('password')) {
                    $payload['password'] = bcrypt($request->password);
                }
                $faculty->user->update($payload);
            } else {
                User::create([
                    'name'       => $name,
                    'username'   => $request->username,
                    'password'   => bcrypt($request->filled('password') ? $request->password : 'password123'),
                    'role'       => 'faculty',
                    'faculty_id' => $faculty->id,
                ]);
            }
        });

        return back()->with('success', 'Faculty updated successfully!');
    }

    public function destroy($id)
    {
        $faculty = Faculty::findOrFail($id);

        DB::transaction(function () use ($faculty) {
            User::where('faculty_id', $faculty->id)->delete();
            $faculty->delete();
        });

        return back()->with('success', 'Faculty account deleted successfully!');
    }

    public function afterSubmit(Request $request){
        DB::beginTransaction();



        try {
            
            if(isset($request->curriculum_id)) {
                $items = $request->itemlist['data'];
                
                foreach ($items as $row) {
                    if(isset($row["id"])) {
                        $day_schedule = implode("|", $row["day_schedule"]);
                        DB::update(
                            "UPDATE curriculum_child SET day_schedule = ? , class_start = ? , class_end = ? , adviser_id =?  WHERE id = ?",
                            [$day_schedule, $row["class_start"], $row["class_end"] , $row["adviser_id"], $row["id"]]
                        );
                    } 
                    
                }

            }  else {
                return back()->with('error', 'Unabled to save the transaction!.');
            }

            DB::commit();
            return back()->with('success', 'Successfully Saved.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function sourceModal(Request $request){
          $result = DB::select(
            "select c.*,
            g.grade_level,
            sy.school_year,
            f.`name` as faculty_name,
            CONCAT(sy.school_year, ' - ', g.grade_level) as `name`
            from curriculum c 
            left join gradelvls g ON g.id = c.grade_id 
            left join schoolyrs sy ON sy.id = c.schoolyr_id
            left join users f ON f.faculty_id = c.adviser_id where c.deleted = ?",
            [0]
        );
       return response()->json([
            'html' => view('auth.admindashboard.partials.scheduledModal', compact('result'))->render(),
            'status' => 'success'
        ]);

    }

    public function getCurriculumSubjects(Request $request){

        if(isset($request->faculty_id)) {
            $child = DB::select(
                "select c.*,
                s.subject_name,
                s.subject_code
                from curriculum_child c 
                left join subjects s ON s.id = c.subject_id
                where c.deleted = ? and c.adviser_id = ?",
                [0, $request->faculty_id]
            );
        } else {
            $request->validate([
                'id' => 'required|int',
            ]);
            
            $child = DB::select(
                "select c.*,
                s.subject_name,
                s.subject_code
                from curriculum_child c 
                left join subjects s ON s.id = c.subject_id
                where c.deleted = ? and c.curriculum_id = ?",
                [0, $request->id]
            );
        }
      
       


        $faculties = Faculty::with('user')->get();

        return response()->json([
            'html' => view('auth.admindashboard.views.faculty.getScheduledDetails', compact('child','faculties'))->render(),
            'status' => 'success'
        ]);

    }
}
