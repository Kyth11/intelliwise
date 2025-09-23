<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faculty;
use App\Models\User;

class FacultyDashboardController extends Controller
{
    /**
     * Show the Faculty Dashboard with faculty list.
     */
    public function index()
    {
        // Fetch all faculty with linked user account
        $faculties = Faculty::with('user')->get();

        return view('auth.facultydashboard', compact('faculties'));
    }

    /**
     * Store a new faculty and linked user login.
     */
    public function store(Request $request)
    {
        $request->validate([
            'f_firstname' => 'required|string',
            'f_lastname'  => 'required|string',
            'f_email'     => 'required|email|unique:faculty,f_email',
            'username'    => 'required|string|unique:users,username',
            'password'    => 'required|string|min:6',
        ]);

        // Create Faculty record
        $faculty = Faculty::create([
            'f_firstname' => $request->f_firstname,
            'f_middlename'=> $request->f_middlename,
            'f_lastname'  => $request->f_lastname,
            'f_address'   => $request->f_address,
            'f_contact'   => $request->f_contact,
            'f_email'     => $request->f_email,
        ]);

        // Create User login for Faculty
        User::create([
            'name'       => $faculty->f_firstname . ' ' . $faculty->f_lastname,
            'username'   => $request->username,
            'password'   => bcrypt($request->password),
            'role'       => 'faculty',
            'faculty_id' => $faculty->id,
        ]);

        return back()->with('success', 'Faculty account created successfully!');
    }

    /**
     * Delete faculty and linked user.
     */
    public function destroy($id)
    {
        $faculty = Faculty::findOrFail($id);

        // Delete linked user first
        User::where('faculty_id', $faculty->id)->delete();

        // Delete faculty record
        $faculty->delete();

        return back()->with('success', 'Faculty account deleted successfully!');
    }
}
