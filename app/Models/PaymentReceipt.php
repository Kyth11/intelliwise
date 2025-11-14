<?php
// app/Models/PaymentReceipt.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class PaymentReceipt extends Model
{
    protected $fillable = [
        'student_id','guardian_id','payment_id','amount','reference_no',
        'method','image_path','notes','status','reviewed_by','reviewed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    // --- Accessors / Mutators ---

    /**
     * Normalize stored path by stripping any leading "public/".
     */
    public function setImagePathAttribute($value): void
    {
        if (is_string($value)) {
            $this->attributes['image_path'] = ltrim(preg_replace('/^public\//', '', $value), '/');
        } else {
            $this->attributes['image_path'] = $value;
        }
    }

    /**
     * Public URL for the uploaded file (works for both images and PDFs).
     */
    public function getPublicUrlAttribute(): ?string
    {
        if (!$this->image_path) return null;
        $clean = ltrim(preg_replace('/^public\//', '', $this->image_path), '/');
        return Storage::disk('public')->url($clean);
    }

    // --- Relations ---

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    // Your payments model is named "Payments" in your project.
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payments::class, 'payment_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
