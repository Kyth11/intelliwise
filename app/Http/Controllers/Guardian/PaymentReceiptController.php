<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\PaymentReceipt;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\AppSetting;

class PaymentReceiptController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,pdf', 'max:5120'], // 5MB
        ]);

        $student = Student::findOrFail($request->student_id);

        // Save uploaded receipt to PUBLIC disk → returns e.g. "receipts/abc123.pdf"
        $path = $request->file('receipt')->store('receipts', 'public');

        // (Optional) If you need the QR URL in future:
        // $gcashQrPath = AppSetting::get('gcash_qr_path'); // e.g. "gcash/xyz.png"
        // $gcashQrUrl  = $gcashQrPath ? Storage::disk('public')->url($gcashQrPath) : null;

        PaymentReceipt::create([
            'student_id' => $student->id,
            'guardian_id' => optional(Auth::user()->guardian)->id,
            'amount' => $request->amount,
            'reference_no' => $request->reference_no,
            'method' => 'G-cash', // keep consistent
            'image_path' => $path,     // relative; resolve with Storage::disk('public')->url()
            'notes' => $request->notes,
            'status' => 'Pending',
        ]);

        return back()->with('success', 'Receipt submitted. We’ll review and confirm shortly.');
    }
}
