<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuarterLock;
use Illuminate\Http\Request;

class GradeQuarterController extends Controller
{
    public function save(Request $request)
    {
        QuarterLock::setGlobal([
            'q1' => $request->boolean('q1'),
            'q2' => $request->boolean('q2'),
            'q3' => $request->boolean('q3'),
            'q4' => $request->boolean('q4'),
        ]);

        return back()->with('success', 'Quarter access updated for ALL grades and ALL students.');
    }
}
