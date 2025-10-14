<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enrollment;

class EnrollmentReportController extends Controller
{
    public function index(Request $request)
    {
        $sy     = $request->string('sy')->toString();
        $status = $request->string('status')->toString();
        $q      = $request->string('q')->toString();

        $enrollments = Enrollment::query()
            ->with([
                'student.guardian',
                'student.gradelvl',
                'student.tuition',
                'student.optionalFees',
                'student.payments',
                'guardian',
                'schoolyr',
            ])
            ->when($sy, fn($qr) => $qr->where('schoolyr_id', $sy))   // filter by schoolyr_id
            ->when($status, fn($qr) => $qr->where('status', $status))
            ->when($q, function ($qr) use ($q) {
                $qr->whereHas('student', function ($s) use ($q) {
                    $s->where('s_firstname','like',"%{$q}%")
                      ->orWhere('s_middlename','like',"%{$q}%")
                      ->orWhere('s_lastname','like',"%{$q}%");
                })->orWhereHas('guardian', function ($g) use ($q) {
                    $g->where('g_firstname','like',"%{$q}%")
                      ->orWhere('g_lastname','like',"%{$q}%")
                      ->orWhere('g_contact','like',"%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // Page totals (live compute)
        $pageTotals = ['tuition'=>0,'optional'=>0,'total_due'=>0,'paid'=>0,'balance'=>0];

        foreach ($enrollments as $en) {
            $s = $en->student;
            if (!$s) continue;

            $base     = (float) ($s->base_tuition ?? 0);
            $optional = (float) ($s->optional_sum ?? 0);
            $total    = $base + $optional;
            $paid     = (float) ($s->payments()->sum('amount') ?? 0);
            $balance  = max(0, round($total - $paid, 2));

            $pageTotals['tuition']   += $base;
            $pageTotals['optional']  += $optional;
            $pageTotals['total_due'] += $total;
            $pageTotals['paid']      += $paid;
            $pageTotals['balance']   += $balance;
        }

        // 👇 match your actual blade path
        return view('auth.admindashboard.reports', compact(
            'enrollments', 'pageTotals', 'sy', 'status', 'q'
        ));
    }
}
