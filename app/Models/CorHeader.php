<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorHeader extends Model
{
    protected $table = 'cor_headers';

    protected $fillable = [
        'student_id',
        'guardian_id',
        'school_year',
        'semester',
        'course_year',
        'registration_no',
        'cor_no',
        'date_enrolled',
        'tuition_fee',
        'misc_fee',
        'other_fees',
        'total_school_fees',
        'signed_by_name',
        'signed_by_user_id',
        'html_snapshot',
    ];

    protected $casts = [
        'date_enrolled'      => 'datetime',
        'tuition_fee'        => 'decimal:2',
        'misc_fee'           => 'decimal:2',
        'other_fees'         => 'decimal:2',
        'total_school_fees'  => 'decimal:2',
    ];

    /**
     * Student relationship (FK = student_id, PK = students.lrn).
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'lrn');
    }

    /**
     * Guardian relationship.
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'guardian_id');
    }

    /**
     * User who signed the COR.
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }
}
