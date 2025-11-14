<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Student;
use App\Models\Payments;
use App\Models\Tuition;

class PaymentsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function store(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $request->headers->set('Accept', 'application/json');
        }

        $data = $request->validate([
            'student_id'      => 'required|exists:students,id',
            'amount'          => 'required|numeric|min:0.01',
            'payment_method'  => 'required|in:Cash,G-cash',
            'current_balance' => 'required|numeric|min:0',
        ]);

        $student        = Student::with('tuition')->findOrFail($data['student_id']);
        $amount         = (float) $data['amount'];
        $currentBalance = (float) $data['current_balance'];

        if ($amount > $currentBalance) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount exceeds the current balance.'
            ], 422);
        }

        $tuitionId = optional($student->tuition)->id;
        if (!$tuitionId && !empty($student->s_gradelvl)) {
            $tuitionId = optional(
                Tuition::where('grade_level', $student->s_gradelvl)->latest('id')->first()
            )->id;
        }
        if (!$tuitionId) {
            return response()->json([
                'success' => false,
                'message' => 'No tuition record found for this student/grade level.'
            ], 422);
        }

        $newBalance    = max($currentBalance - $amount, 0);
        $paymentStatus = $newBalance <= 0 ? 'Paid' : 'Partial'; // matches new enum

        try {
            DB::transaction(function () use ($student, $tuitionId, $amount, $paymentStatus, $newBalance, $data) {
                Payments::create([
                    'tuition_id'     => $tuitionId,
                    'amount'         => $amount,
                    'payment_method' => $data['payment_method'],
                    'payment_status' => $paymentStatus,
                    'balance'        => $newBalance,
                ]);

                $student->update([
                    's_total_due'    => $newBalance,
                    'payment_status' => $paymentStatus,
                ]);
            });

            return response()->json([
                'success'        => true,
                'new_balance'    => $newBalance,
                'payment_status' => $paymentStatus,
                'paid'           => $amount,
            ]);
        } catch (\Throwable $e) {
            Log::error('Payment store failed', [
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
                'student_id' => $student->id ?? null,
                'tuition_id' => $tuitionId ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save payment. Please check server logs.'
            ], 500);
        }
    }
}
