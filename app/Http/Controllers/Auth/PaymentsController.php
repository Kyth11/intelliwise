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
        // For AJAX / fetch JSON calls
        if ($request->ajax() || $request->wantsJson()) {
            $request->headers->set('Accept', 'application/json');
        }

        // Treat both student_lrn and student_id as LRN (string)
        $data = $request->validate([
            'student_lrn'     => 'nullable|string|exists:students,lrn',
            'student_id'      => 'nullable|string|exists:students,lrn',
            'amount'          => 'required|numeric|min:0.01',
            'payment_method'  => 'required|in:Cash,G-cash',
            'current_balance' => 'required|numeric|min:0',
        ]);

        $studentLrn = $data['student_lrn'] ?? $data['student_id'] ?? null;

        if (!$studentLrn) {
            return response()->json([
                'success' => false,
                'message' => 'Student LRN is required.',
            ], 422);
        }

        // Resolve student by LRN (PK is lrn)
        $student = Student::with('tuition')
            ->where('lrn', $studentLrn)
            ->firstOrFail();

        $amount         = (float) $data['amount'];
        $currentBalance = (float) $data['current_balance'];

        if ($amount > $currentBalance) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount exceeds the current balance.',
            ], 422);
        }

        // Determine tuition record (prefer attached tuition, else grade-level)
        $tuitionId = optional($student->tuition)->id;

        if (!$tuitionId && !empty($student->s_gradelvl)) {
            $tuitionId = optional(
                Tuition::where('grade_level', $student->s_gradelvl)
                    ->latest('id')
                    ->first()
            )->id;
        }

        if (!$tuitionId) {
            return response()->json([
                'success' => false,
                'message' => 'No tuition record found for this student/grade level.',
            ], 422);
        }

        $newBalance    = max($currentBalance - $amount, 0);
        $paymentStatus = $newBalance <= 0 ? 'Paid' : 'Partial';

        try {
            DB::transaction(function () use ($student, $studentLrn, $tuitionId, $amount, $paymentStatus, $newBalance, $data) {
                // Key fix: actually store student_id per payment
                Payments::create([
                    'student_id'     => $studentLrn,                    // LRN
                    'tuition_id'     => $tuitionId,
                    'amount'         => $amount,
                    'payment_method' => $data['payment_method'],
                    'payment_status' => $paymentStatus,
                    'balance'        => $newBalance,
                    'schoolyr_id'    => $student->schoolyr_id ?? null,
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
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
                'student_lrn' => $studentLrn,
                'tuition_id'  => $tuitionId ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save payment. Please check server logs.',
            ], 500);
        }
    }
}
