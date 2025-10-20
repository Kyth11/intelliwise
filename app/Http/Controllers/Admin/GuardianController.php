<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GuardianController extends Controller
{
    public function index()
    {
        $guardians = Guardian::with(['students', 'user'])->get();
        $students  = Student::with('guardian')->get();

        return view('auth.admindashboard.guardians', compact('guardians', 'students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'g_firstname' => 'required|string|max:255',
            'g_middlename'=> 'nullable|string|max:255',
            'g_lastname'  => 'required|string|max:255',
            'g_email'     => 'nullable|email|max:255|unique:guardians,g_email',
            'g_address'   => 'nullable|string|max:255',
            'g_contact'   => 'nullable|string|max:255',
            'username'    => 'required|string|max:255|unique:users,username',
            'password'    => 'required|string|min:6',
        ]);

        DB::transaction(function () use ($request) {
            $guardian = Guardian::create([
                'g_address'    => $request->g_address,
                'g_contact'    => $request->g_contact,
                'g_email'      => $request->g_email,
                'm_firstname'  => $request->g_firstname,
                'm_middlename' => $request->g_middlename,
                'm_lastname'   => $request->g_lastname,
                'f_firstname'  => null,
                'f_middlename' => null,
                'f_lastname'   => null,
            ]);

            User::create([
                'name'        => trim($request->g_firstname.' '.$request->g_lastname),
                'username'    => $request->username,
                'password'    => bcrypt($request->password),
                'role'        => 'guardian',
                'guardian_id' => $guardian->id,
            ]);
        });

        return back()->with('success', 'Guardian account created successfully!');
    }

    public function update(Request $request, $id)
    {
        $guardian = Guardian::with('user')->findOrFail($id);
        $currentUserId = optional($guardian->user)->id;

        $request->validate([
            'g_firstname' => 'nullable|string|max:255',
            'g_middlename'=> 'nullable|string|max:255',
            'g_lastname'  => 'nullable|string|max:255',
            'g_email'     => 'nullable|email|max:255|unique:guardians,g_email,' . $guardian->id,
            'g_address'   => 'nullable|string|max:255',
            'g_contact'   => 'nullable|string|max:255',
            'username'    => ['required','string','max:255', Rule::unique('users','username')->ignore($currentUserId)],
            'password'    => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($request, $guardian) {
            $data = [
                'g_address' => $request->g_address,
                'g_contact' => $request->g_contact,
                'g_email'   => $request->g_email,
            ];

            if ($request->filled('g_firstname'))  $data['m_firstname']  = $request->g_firstname;
            if ($request->filled('g_middlename')) $data['m_middlename'] = $request->g_middlename;
            if ($request->filled('g_lastname'))   $data['m_lastname']   = $request->g_lastname;

            $guardian->update($data);
            $guardian->refresh();

            $displayFirst = $guardian->m_firstname ?: $guardian->f_firstname ?: '';
            $displayLast  = $guardian->m_lastname  ?: $guardian->f_lastname  ?: '';

            if ($guardian->user) {
                $payload = [
                    'name'     => trim($displayFirst.' '.$displayLast),
                    'username' => $request->username,
                ];
                if ($request->filled('password')) {
                    $payload['password'] = bcrypt($request->password);
                }
                $guardian->user->update($payload);
            } else {
                User::create([
                    'name'        => trim($displayFirst.' '.$displayLast),
                    'username'    => $request->username,
                    'password'    => bcrypt($request->filled('password') ? $request->password : 'password123'),
                    'role'        => 'guardian',
                    'guardian_id' => $guardian->id,
                ]);
            }
        });

        return back()->with('success', 'Guardian updated successfully!');
    }

    public function destroy($id)
    {
        $guardian = Guardian::findOrFail($id);

        DB::transaction(function () use ($guardian) {
            User::where('guardian_id', $guardian->id)->delete();
            $guardian->delete();
        });

        return back()->with('success', 'Guardian account deleted successfully!');
    }
}
