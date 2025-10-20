<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /** Self-update (no id in route) */
    public function updateSelf(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'faculty') {
            abort(403, 'Unauthorized');
        }

        $faculty = $user->faculty_id ? Faculty::find($user->faculty_id) : null;
        if (!$faculty) {
            $faculty = new Faculty();
        }

        $request->validate([
            'f_firstname' => 'required|string|max:255',
            'f_lastname'  => 'required|string|max:255',
            'f_email'     => 'nullable|email|max:255|unique:faculties,f_email,' . ($faculty->id ?? 'NULL'),
            'f_address'   => 'nullable|string|max:255',
            'f_contact'   => 'nullable|string|max:255',

            'username'    => 'required|string|max:255|unique:users,username,' . $user->id,
            'password'    => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($request, $user, $faculty) {
            $faculty->f_firstname  = $request->f_firstname;
            $faculty->f_middlename = $request->f_middlename;
            $faculty->f_lastname   = $request->f_lastname;
            $faculty->f_address    = $request->f_address;
            $faculty->f_contact    = $request->f_contact;
            $faculty->f_email      = $request->f_email;
            $faculty->save();

            if (!$user->faculty_id) {
                $user->faculty_id = $faculty->id;
            }

            $user->name     = trim($faculty->f_firstname . ' ' . $faculty->f_lastname);
            $user->username = $request->username;
            if ($request->filled('password')) {
                $user->password = bcrypt($request->password);
            }
            $user->save();
        });

        return back()->with('success', 'Profile saved.');
    }

    /** Admin or self (by id) */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role === 'faculty' && $user->faculty_id != $id) {
            abort(403, 'Unauthorized');
        }

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
}
