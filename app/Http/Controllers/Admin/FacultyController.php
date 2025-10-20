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

class FacultyController extends Controller
{
    public function index()
    {
        $faculties = Faculty::with([
            'user',
            'schedules.subject',
            'schedules.gradelvl',
        ])->get();

        $subjects  = Subjects::all();
        $gradelvls = Gradelvl::all();

        return view('auth.admindashboard.faculties', compact(
            'faculties',
            'subjects',
            'gradelvls'
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
}
