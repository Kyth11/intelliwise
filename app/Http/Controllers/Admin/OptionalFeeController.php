<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OptionalFee;
use Illuminate\Http\Request;

class OptionalFeeController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'scope'  => ['nullable', 'in:grade,student,both'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        OptionalFee::create([
            'name'   => $data['name'],
            'amount' => $data['amount'],
            'scope'  => $data['scope'] ?? 'both',
            'active' => $request->boolean('active'),
        ]);

        return back()->with('success', 'Optional fee added.');
    }

    public function update(Request $request, $id)
    {
        $fee = OptionalFee::findOrFail($id);

        $data = $request->validate([
            'name'   => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'scope'  => ['nullable', 'in:grade,student,both'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        $fee->update([
            'name'   => $data['name'],
            'amount' => $data['amount'],
            'scope'  => $data['scope'] ?? $fee->scope,
            'active' => $request->boolean('active'),
        ]);

        return back()->with('success', 'Optional fee updated.');
    }

    public function destroy($id)
    {
        $fee = OptionalFee::findOrFail($id);
        $fee->tuitions()->detach();
        $fee->students()->detach();
        $fee->delete();

        return back()->with('success', 'Optional fee deleted.');
    }
}
