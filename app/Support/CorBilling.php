<?php

namespace App\Support;

use App\Models\Student;
use App\Models\Tuition;
use App\Models\OptionalFee;
use Illuminate\Support\Collection;

class CorBilling
{
    /**
     * Builds/augments the $billing array for COR using Tuition + Optional Fees.
     *
     * @param  Student  $student
     * @param  Tuition  $tuition
     * @param  array    $baseBilling  Existing billing data (subjects, date_enrolled, etc.)
     * @return array
     */
    public static function fromTuition(Student $student, Tuition $tuition, array $baseBilling = []): array
    {
        $billing = $baseBilling;

        // Tuition yearly
        $tuitionFee = (float) ($billing['tuition_fee'] ?? $tuition->tuition_yearly ?? 0);

        // Misc yearly (fallback to monthly * 10 if yearly is null)
        $miscFee = (float) (
            $billing['misc_fee']
            ?? $tuition->misc_yearly
            ?? ($tuition->misc_monthly ? $tuition->misc_monthly * 10 : 0)
        );

        // Enrollment / registration fee
        $enrollFee = (float) (
            $billing['enrollment_fee']
            ?? $tuition->registration_fee
            ?? 0
        );

        // Grade-level optional fees
        $gradeOpt = $tuition->optionalFees()->active()->get();

        // Student-specific optional fees (if relation exists)
        $studentOpt = method_exists($student, 'optionalFees')
            ? $student->optionalFees()->active()->get()
            : collect();

        // Merge and de-duplicate (by id)
        /** @var Collection $optional */
        $optional = $gradeOpt->merge($studentOpt)->unique('id');

        $optionalTotal = (float) $optional->sum('amount');

        // Map to simple array for the Blade
        $optionalForView = $optional->map(function (OptionalFee $fee) {
            return [
                'id'     => $fee->id,
                'name'   => $fee->name,
                'amount' => (float) $fee->amount,
            ];
        })->values()->all();

        $billing['tuition_fee']       = $tuitionFee;
        $billing['misc_fee']          = $miscFee;
        $billing['enrollment_fee']    = $enrollFee;
        $billing['optional_fees']     = $optionalForView;
        $billing['other_fees']        = $optionalTotal;

        // If the total was not precomputed, compute it now.
        $billing['total_school_fees'] = (float) ($billing['total_school_fees'] ?? 0);
        if ($billing['total_school_fees'] === 0.0) {
            $billing['total_school_fees'] = $tuitionFee + $miscFee + $enrollFee + $optionalTotal;
        }

        return $billing;
    }
}
