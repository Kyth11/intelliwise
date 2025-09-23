<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;

class GuardianDashboardController extends Controller
{
    /**
     * Show Guardian Dashboard with guardian + student info.
     */
    public function index()
    {
        // Fetch guardians with linked student and user
        $guardians = Guardian::with(['student', 'user'])->get();
        $students  = Student::all();

        return view('auth.guardiandashboard', compact('guardians', 'students'));
    }

    /**
     * Store Guardian + linked user login.
     */
    public function store(Request $request)
    {
        $request->validate([
            'g_firstname' => 'required|string',
            'g_lastname'  => 'required|string',
            'g_email'     => 'required|email|unique:guardian,g_email',
            'username'    => 'required|string|unique:users,username',
            'password'    => 'required|string|min:6',
        ]);

        // Create Guardian record
        $guardian = Guardian::create([
            'g_firstname'  => $request->g_firstname,
            'g_middlename' => $request->g_middlename,
            'g_lastname'   => $request->g_lastname,
            'g_address'    => $request->g_address,
            'g_contact'    => $request->g_contact,
            'g_email'      => $request->g_email,
        ]);

        // Create User login for Guardian
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
     * Delete guardian and linked user.
     */
    public function destroy($id)
    {
        $guardian = Guardian::findOrFail($id);

        // Delete linked user first
        User::where('guardian_id', $guardian->id)->delete();

        // Delete guardian record
        $guardian->delete();

        return back()->with('success', 'Guardian account deleted successfully!');
    }
}
