<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Fetch only enrolled students
        $students = Student::where('enrollment_status', 'Enrolled')->get();

        return view('auth.admindashboard', compact('students'));
    }
}

