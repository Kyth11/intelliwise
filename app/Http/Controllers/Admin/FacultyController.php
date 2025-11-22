<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Subjects;
use App\Models\Gradelvl;
use App\Mail\FacultyCredentialsMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
        // Basic validation for faculty data only
        $validated = $request->validate([
            'f_firstname' => 'required|string|max:255',
            'f_middlename'=> 'nullable|string|max:255',
            'f_lastname'  => 'required|string|max:255',
            'f_email'     => 'nullable|email|max:255|unique:faculties,f_email',
            'f_address'   => 'nullable|string|max:255',
            'f_contact'   => 'nullable|string|max:255',
        ]);

        $faculty       = null;
        $username      = null;
        $plainPassword = null;

        DB::transaction(function () use ($validated, &$faculty, &$username, &$plainPassword) {
            // Base username from firstname + lastname
            $base = preg_replace(
                '/\s+/',
                '',
                ($validated['f_firstname'] ?? '') . ($validated['f_lastname'] ?? '')
            );

            $base = strtolower($base);

            // Fallback if somehow empty
            if ($base === '') {
                $base = 'faculty';
            }

            // Ensure username is unique among users
            $username = $base;
            $suffix   = 1;
            while (User::where('username', $username)->exists()) {
                $username = $base . $suffix;
                $suffix++;
            }

            // Plain password is username base + 123 (not the suffixed one)
            $plainPassword = $base . '123';

            // Create Faculty record
            $faculty = Faculty::create([
                'f_firstname'  => $validated['f_firstname'],
                'f_middlename' => $validated['f_middlename'] ?? null,
                'f_lastname'   => $validated['f_lastname'],
                'f_address'    => $validated['f_address'] ?? null,
                'f_contact'    => $validated['f_contact'] ?? null,
                'f_email'      => $validated['f_email'] ?? null,
            ]);

            // Name field for User
            $name = $faculty->full_name ?: ($faculty->f_firstname . ' ' . $faculty->f_lastname);

            // Create linked User record
            User::create([
                'name'       => $name,
                'username'   => $username,
                'password'   => bcrypt($plainPassword),
                'role'       => 'faculty',
                'faculty_id' => $faculty->id,
            ]);
        });

        // Send credentials email only if email is provided
        if ($faculty && !empty($faculty->f_email)) {
            Mail::to($faculty->f_email)->send(
                new FacultyCredentialsMail($faculty, $username, $plainPassword)
            );
        }

        return back()->with('success', 'Faculty account created successfully and credentials emailed!');
    }

    public function update(Request $request, $id)
    {
        $faculty = Faculty::with('user')->findOrFail($id);
        $currentUserId = optional($faculty->user)->id;

        $request->validate([
            'f_firstname' => 'required|string|max:255',
            'f_middlename'=> 'nullable|string|max:255',
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
