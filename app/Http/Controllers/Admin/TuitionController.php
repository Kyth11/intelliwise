<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tuition;
use Illuminate\Http\Request;

class TuitionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'grade_level'       => ['required', 'string', 'max:50'],
            'tuition_monthly'   => ['nullable', 'numeric', 'min:0'],
            'tuition_yearly'    => ['nullable', 'numeric', 'min:0'],
            'misc_monthly'      => ['nullable', 'numeric', 'min:0'],
            'misc_yearly'       => ['nullable', 'numeric', 'min:0'],
            'books_desc'        => ['nullable', 'string', 'max:255'],
            'books_amount'      => ['nullable', 'numeric', 'min:0'],
            'optional_fee_ids'  => ['array'],
            'optional_fee_ids.*'=> ['integer', 'exists:optional_fees,id'],
            'school_year'       => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
        ]);

        $months = 10;

        $tMon  = $data['tuition_monthly'] ?? null;
        $tYear = $data['tuition_yearly'] ?? null;
        if (is_null($tMon) && is_null($tYear)) {
            return back()->withInput()->with('error', 'Enter tuition monthly or yearly.');
        }
        if (is_null($tMon))  $tMon  = $tYear / $months;
        if (is_null($tYear)) $tYear = $tMon * $months;

        $mMon  = $data['misc_monthly'] ?? null;
        $mYear = $data['misc_yearly'] ?? null;
        if (!is_null($mMon) && is_null($mYear)) $mYear = $mMon * $months;
        if (is_null($mMon) && !is_null($mYear)) $mMon = $mYear / $months;

        $books = $request->filled('books_amount') ? (float) $data['books_amount'] : 0;

        $baseTotal = round(($tYear ?? 0) + ($mYear ?? 0) + ($books ?: 0), 2);

        $tuition = Tuition::create([
            'grade_level'     => $data['grade_level'],
            'tuition_monthly' => round($tMon, 2),
            'tuition_yearly'  => round($tYear, 2),
            'misc_monthly'    => $mMon !== null ? round($mMon, 2) : null,
            'misc_yearly'     => $mYear !== null ? round($mYear, 2) : null,
            'books_desc'      => $data['books_desc'] ?? null,
            'books_amount'    => $request->filled('books_amount') ? round($books, 2) : null,
            'school_year'     => $data['school_year'] ?? null,
            'total_yearly'    => 0,
        ]);

        $ids = collect($data['optional_fee_ids'] ?? [])->filter()->unique()->values();
        if ($ids->isNotEmpty()) $tuition->optionalFees()->sync($ids);

        $optSum = $tuition->optionalFees()->sum('amount');
        $tuition->update(['total_yearly' => round($baseTotal + $optSum, 2)]);

        return back()->with('success', 'Tuition saved.');
    }

    public function update(Request $request, $id)
    {
        $tuition = Tuition::findOrFail($id);

        $data = $request->validate([
            'grade_level'       => ['required', 'string', 'max:50'],
            'tuition_monthly'   => ['nullable', 'numeric', 'min:0'],
            'tuition_yearly'    => ['nullable', 'numeric', 'min:0'],
            'misc_monthly'      => ['nullable', 'numeric', 'min:0'],
            'misc_yearly'       => ['nullable', 'numeric', 'min:0'],
            'books_desc'        => ['nullable', 'string', 'max:255'],
            'books_amount'      => ['nullable', 'numeric', 'min:0'],
            'optional_fee_ids'  => ['array'],
            'optional_fee_ids.*'=> ['integer', 'exists:optional_fees,id'],
            'school_year'       => ['nullable', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
        ]);

        $months = 10;

        $tMon  = $request->input('tuition_monthly');
        $tYear = $request->input('tuition_yearly');
        if (is_null($tMon) && is_null($tYear)) {
            $tMon  = (float) $tuition->tuition_monthly;
            $tYear = (float) $tuition->tuition_yearly;
        } elseif (is_null($tMon)) {
            $tMon = $tYear / $months;
        } elseif (is_null($tYear)) {
            $tYear = $tMon * $months;
        }

        $mMon  = $request->input('misc_monthly');
        $mYear = $request->input('misc_yearly');
        if (is_null($mMon) && is_null($mYear)) {
            $mMon  = $tuition->misc_monthly;
            $mYear = $tuition->misc_yearly;
        } elseif (is_null($mMon)) {
            $mMon = $mYear / $months;
        } elseif (is_null($mYear)) {
            $mYear = $mMon * $months;
        }

        $books = $request->filled('books_amount')
            ? (float) $request->input('books_amount')
            : ($tuition->books_amount ?? 0);

        $tuition->update([
            'grade_level'     => $data['grade_level'],
            'tuition_monthly' => round($tMon, 2),
            'tuition_yearly'  => round($tYear, 2),
            'misc_monthly'    => $mMon !== null ? round($mMon, 2) : null,
            'misc_yearly'     => $mYear !== null ? round($mYear, 2) : null,
            'books_desc'      => $data['books_desc'] ?? null,
            'books_amount'    => $request->filled('books_amount') ? round($books, 2) : null,
            'school_year'     => $data['school_year'] ?? null,
        ]);

        $ids = collect($data['optional_fee_ids'] ?? [])->filter()->unique()->values();
        $tuition->optionalFees()->sync($ids);

        $baseTotal = round(($tYear ?? 0) + ($mYear ?? 0) + ($books ?: 0), 2);
        $optSum    = $tuition->optionalFees()->sum('amount');

        $tuition->update(['total_yearly' => round($baseTotal + $optSum, 2)]);

        return back()->with('success', 'Tuition updated successfully!');
    }

    public function destroy($id)
    {
        Tuition::findOrFail($id)->delete();
        return back()->with('success', 'Tuition deleted successfully!');
    }
}
