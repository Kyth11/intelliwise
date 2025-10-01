<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;

class GuardianDashboardController extends Controller
{
    public function index()
    {
        $guardians = Guardian::with(['students', 'user'])->get();
        $students  = Student::with('guardian')->get();

        return view('auth.guardiandashboard', compact('guardians', 'students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'g_firstname' => 'required|string|max:255',
            'g_lastname'  => 'required|string|max:255',
            'g_email'     => 'nullable|email|max:255|unique:guardians,g_email',
            'username'    => 'required|string|max:255|unique:users,username',
            'password'    => 'required|string|min:6',
        ]);

        $guardian = Guardian::create([
            'g_firstname'  => $request->g_firstname,
            'g_middlename' => $request->g_middlename,
            'g_lastname'   => $request->g_lastname,
            'g_address'    => $request->g_address,
            'g_contact'    => $request->g_contact,
            'g_email'      => $request->g_email,
        ]);

        User::create([
            'name'        => $guardian->g_firstname . ' ' . $guardian->g_lastname,
            'username'    => $request->username,
            'password'    => bcrypt($request->password),
            'role'        => 'guardian',
            'guardian_id' => $guardian->id,
        ]);

        return back()->with('success', 'Guardian account created successfully!');
    }

    public function edit($id)
    {
        $guardian = Guardian::with('user')->findOrFail($id);
        return view('auth.editguardians', compact('guardian'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // Guardians can only update their own account
        if ($user->role === 'guardian' && $user->guardian_id != $id) {
            abort(403, 'Unauthorized');
        }

        $guardian = Guardian::with('user')->findOrFail($id);

        $request->validate([
            'g_firstname' => 'required|string|max:255',
            'g_lastname'  => 'required|string|max:255',
            'g_email'     => 'nullable|email|max:255|unique:guardians,g_email,' . $guardian->id,
            'username'    => 'required|string|max:255|unique:users,username,' . ($guardian->user->id ?? 'NULL'),
            'password'    => 'nullable|string|min:6',
        ]);

        $guardian->update([
            'g_firstname'  => $request->g_firstname,
            'g_middlename' => $request->g_middlename,
            'g_lastname'   => $request->g_lastname,
            'g_address'    => $request->g_address,
            'g_contact'    => $request->g_contact,
            'g_email'      => $request->g_email,
        ]);

        if ($guardian->user) {
            $guardian->user->update([
                'name'     => $guardian->g_firstname . ' ' . $guardian->g_lastname,
                'username' => $request->username,
                'password' => $request->filled('password')
                                ? bcrypt($request->password)
                                : $guardian->user->password,
            ]);
        } else {
            if ($request->filled('username') && $request->filled('password')) {
                User::create([
                    'name'        => $guardian->g_firstname . ' ' . $guardian->g_lastname,
                    'username'    => $request->username,
                    'password'    => bcrypt($request->password),
                    'role'        => 'guardian',
                    'guardian_id' => $guardian->id,
                ]);
            }
        }

        return back()->with('success', 'Guardian updated successfully!');
    }

    public function destroy($id)
    {
        $guardian = Guardian::findOrFail($id);

        User::where('guardian_id', $guardian->id)->delete();
        $guardian->delete();

        return back()->with('success', 'Guardian account deleted successfully!');
    }
}
