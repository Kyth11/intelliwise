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
            // You can keep this if you later add a `scope` column,
            // but right now your DB table does NOT have `scope`.
            'scope'  => ['nullable', 'in:grade,student,both'],
            'active' => ['nullable', 'in:0,1'],
        ]);

        // Default to active=true when the checkbox is not present
        $active = $request->has('active')
            ? $request->boolean('active')
            : true;

        OptionalFee::create([
            'name'   => $data['name'],
            'amount' => $data['amount'],
            // This will have effect only if you add a `scope` column in DB.
            'scope'  => $data['scope'] ?? 'both',
            'active' => $active,
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

        // If the checkbox is absent, keep the previous active value
        $active = $request->has('active')
            ? $request->boolean('active')
            : (bool) $fee->active;

        $fee->update([
            'name'   => $data['name'],
            'amount' => $data['amount'],
            'scope'  => $data['scope'] ?? $fee->scope,
            'active' => $active,
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
