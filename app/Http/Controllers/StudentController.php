<?php
namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            's_firstname' => 'required|string',
            's_lastname' => 'required|string',
            's_contact' => 'required|string',
            's_address' => 'required|string',
        ]);

        try {
            Student::create($request->all());

            return redirect()->route('admin.dashboard')
                             ->with('success', 'Student enrolled successfully!');
        } catch (\Exception $e) {
            return redirect()->route('admin.dashboard')
                             ->with('error', 'Failed to enroll student. Please try again.');
        }
    }
   public function destroy($id)
{
    try {
        $student = Student::findOrFail($id);
        $student->delete();

        return redirect()->back()->with('success', 'Student archived successfully.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Failed to archive student.');
    }
}

}
