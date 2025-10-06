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
    /**
     * Smart entry:
     * - If the logged-in user is a GUARDIAN → show the guardian dashboard (resources/views/auth/guardiandashboard.blade.php)
     * - Otherwise (admin)                   → show the admin guardians management page (resources/views/auth/admindashboard/guardians.blade.php)
     */
    public function index()
    {
        $user = Auth::user();

        // GUARDIAN PANEL (self)
        if ($user && $user->role === 'guardian') {
            $guardian = Guardian::with(['students', 'user'])->find($user->guardian_id);

            // Simple stats (adjust if you have real data sources)
            $stats = [
                'children_count'  => $guardian ? $guardian->students->count() : 0,
                'pending_reports' => 0,
                'messages_count'  => 0,
            ];

            // resources/views/auth/guardiandashboard.blade.php
            return view('auth.guardiandashboard', compact('guardian', 'stats'));
        }

        // ADMIN MANAGEMENT PAGE
        $guardians = Guardian::with(['students', 'user'])->get();

        // (Optional) if your admin page wants a flat students list too:
        $students = Student::with('guardian')->get();

        // resources/views/auth/admindashboard/guardians.blade.php
        return view('auth.admindashboard.guardians', compact('guardians', 'students'));
    }

    /**
     * Create a guardian (admin only).
     */
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

    /**
     * Edit form (admin).
     */
    public function edit($id)
    {
        $guardian = Guardian::with('user')->findOrFail($id);
        return view('auth.editguardians', compact('guardian'));
    }

    /**
     * Update guardian (admin or self).
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // Guardians can only update their own account
        if ($user->role === 'guardian' && (int)$user->guardian_id !== (int)$id) {
            abort(403, 'Unauthorized');
        }

        $guardian = Guardian::with('user')->findOrFail($id);

        $request->validate([
            'g_firstname' => 'required|string|max:255',
            'g_lastname'  => 'required|string|max:255',
            'g_email'     => 'nullable|email|max:255|unique:guardians,g_email,' . $guardian->id,
            'username'    => 'required|string|max:255|unique:users,username,' . optional($guardian->user)->id,
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
            // Create user record if missing and creds provided
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

    /**
     * Delete guardian (admin only).
     */
    public function destroy($id)
    {
        $guardian = Guardian::findOrFail($id);

        User::where('guardian_id', $guardian->id)->delete();
        $guardian->delete();

        return back()->with('success', 'Guardian account deleted successfully!');
    }
}
