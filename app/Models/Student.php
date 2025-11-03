<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;  // <---- added

class Student extends Model
{

     use SoftDeletes; // <---- added

    protected $fillable = [
        // existing...
        's_firstname','s_middlename','s_lastname',
        's_birthdate','s_address','s_citizenship','s_religion',
        's_contact','s_email',
        'guardian_id',
        's_gradelvl','gradelvl_id',
        's_tuition_sum','tuition_id',
        's_optional_total','s_total_due',
        'enrollment_status','payment_status',
        'schoolyr_id', // <---- added
    ];
    protected $casts = [
        's_optional_total' => 'decimal:2',
        's_total_due'      => 'decimal:2',
    ];

    protected $appends = ['full_name'];

    /** Household */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'guardian_id');
    }

    /** Grade level FK */
    public function gradelvl(): BelongsTo
    {
        return $this->belongsTo(Gradelvl::class, 'gradelvl_id');
    }

    /** Tuition plan chosen by this student */
    public function tuition(): BelongsTo
    {
        return $this->belongsTo(Tuition::class, 'tuition_id');
    }

    /**
     * ✅ Optional fees directly attached to the student (via pivot).
     * Pivot table in your migration is `optional_fee_student`
     * with columns: optional_fee_id, student_id, timestamps.
     */
    public function optionalFees(): BelongsToMany
    {
        return $this->belongsToMany(
            OptionalFee::class,
            'optional_fee_student',   // <- correct table name
            'student_id',             // FK to students
            'optional_fee_id'         // FK to optional_fees
        )->withTimestamps();
        // ->withPivot('amount_override'); // not present in your schema
    }

    /**
     * Payments associated with student's tuition.
     * Your `payments` table uses tuition_id (not student_id).
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payments::class, 'tuition_id', 'tuition_id');
    }

    /** Convenience accessor for full name */
    public function getFullNameAttribute(): string
    {
        $mid = trim((string)($this->s_middlename ?? ''));
        $name = trim(implode(' ', array_filter([$this->s_firstname ?? '', $mid, $this->s_lastname ?? ''])));
        return $name !== '' ? $name : ('Student #'.$this->id);
    }

    /* ---------- Computed accessors ---------- */

    /** Base tuition with safe fallbacks for legacy rows */
    public function getBaseTuitionAttribute(): float
    {
        if ($this->relationLoaded('tuition') && $this->tuition) {
            return (float) $this->tuition->total_yearly;
        }
        if (!empty($this->s_tuition_sum)) {
            return (float) preg_replace('/[^\d.]+/', '', (string) $this->s_tuition_sum);
        }
        $row = Tuition::where('grade_level', $this->s_gradelvl)
                ->orderByDesc('updated_at')->orderByDesc('created_at')->first();
        return (float) ($row->total_yearly ?? 0);
    }

    /** Sum of student-selected optional fees */
    public function getOptionalSumAttribute(): float
    {
        $fees = $this->relationLoaded('optionalFees') ? $this->optionalFees : $this->optionalFees()->get();
        return (float) $fees->sum(function ($f) {
            // no amount_override column in your pivot; fallback to fee amount
            return (float) ($f->amount ?? 0);
        });
    }

    /** Tuition + optional (no payments deduction) */
    public function getTotalDueAttribute(): float
    {
        return round($this->base_tuition + $this->optional_sum, 2);
    }

    /** With payments (if you add them later) */
    public function getComputedBalanceAttribute(): float
    {
        $paid = (float) ($this->payments()->sum('amount') ?? 0);
        $bal  = ($this->base_tuition + $this->optional_sum) - $paid;
        return $bal > 0 ? round($bal, 2) : 0.0;
    }

      public function schoolyr(): BelongsTo { return $this->belongsTo(Schoolyr::class, 'schoolyr_id'); }
}
