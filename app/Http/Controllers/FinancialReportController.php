<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payments;
use App\Models\Schoolyr;
use App\Models\Gradelvl;

class FinancialReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(Request $request)
    {
        $sy_id       = $request->filled('sy_id')       ? (int) $request->input('sy_id') : null;
        $gradelvl_id = $request->filled('gradelvl_id') ? (int) $request->input('gradelvl_id') : null;
        $student_id  = $request->filled('student_id')  ? (int) $request->input('student_id') : null;
        $method      = (string) $request->input('payment_method', '');
        $status      = (string) $request->input('payment_status', '');
        $q           = trim((string) $request->input('q', ''));
        $dateFrom    = $request->input('date_from');
        $dateTo      = $request->input('date_to');

        // Default to active School Year if none selected
        if (!$sy_id) {
            $currentSy = Schoolyr::where('active', true)->first();
            if ($currentSy) {
                $sy_id = (int) $currentSy->id;
            }
        }

        $perPage = 50;

        $baseQuery = Payments::query()
            ->with([
                'student.gradelvl',
                'schoolyr',
            ])
            ->when($sy_id, fn($qr) => $qr->where('schoolyr_id', $sy_id))
            ->when($gradelvl_id, function ($qr) use ($gradelvl_id) {
                $qr->whereHas('student', fn($s) => $s->where('gradelvl_id', $gradelvl_id));
            })
            ->when($student_id, function ($qr) use ($student_id) {
                $qr->whereHas('student', fn($s) => $s->where('id', $student_id));
            })
            ->when($method !== '', fn($qr) => $qr->where('payment_method', $method))
            ->when($status !== '', fn($qr) => $qr->where('payment_status', $status))
            ->when($dateFrom, fn($qr) => $qr->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($qr) => $qr->whereDate('created_at', '<=', $dateTo))
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($sub) use ($q) {
                    $sub->whereHas('student', function ($s) use ($q) {
                        $s->where('s_firstname', 'like', "%{$q}%")
                          ->orWhere('s_middlename', 'like', "%{$q}%")
                          ->orWhere('s_lastname', 'like', "%{$q}%")
                          ->orWhere('s_contact', 'like', "%{$q}%")
                          ->orWhere('s_email', 'like', "%{$q}%");
                    })
                    ->orWhere('payment_method', 'like', "%{$q}%")
                    ->orWhere('payment_status', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at');

        $payments = $baseQuery->paginate($perPage)->appends($request->query());

        $pageIn  = (float) $payments->sum('amount');
        $totalIn = (float) ((clone $baseQuery)->selectRaw('COALESCE(SUM(amount),0) as agg')->value('agg') ?? 0);

        $schoolyrs = Schoolyr::orderBy('school_year', 'desc')->get(['id', 'school_year']);
        $gradelvls = Gradelvl::orderBy('grade_level')->get(['id', 'grade_level']);

        return view('auth.admindashboard.reports-financial', [
            'payments'    => $payments,
            'pageIn'      => $pageIn,
            'totalIn'     => $totalIn,
            'schoolyrs'   => $schoolyrs,
            'gradelvls'   => $gradelvls,
            'sy_id'       => $sy_id,
            'gradelvl_id' => $gradelvl_id,
            'student_id'  => $student_id,
            'method'      => $method,
            'status'      => $status,
            'q'           => $q,
            'dateFrom'    => $dateFrom,
            'dateTo'      => $dateTo,
        ]);
    }
}
