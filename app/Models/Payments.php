<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payments extends Model
{
    use SoftDeletes;

    protected $table = 'payments';

    protected $fillable = [
        'student_id',        // LRN of the student (FK → students.lrn)
        'amount',
        'payment_method',    // 'Cash' | 'G-cash'
        'payment_status',    // 'Paid' | 'Unpaid' | 'Partial'
        'balance',
        'tuition_id',
        'schoolyr_id',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        // payments.student_id → students.lrn
        return $this->belongsTo(Student::class, 'student_id', 'lrn');
    }

    public function tuition(): BelongsTo
    {
        return $this->belongsTo(Tuition::class, 'tuition_id');
    }

    public function schoolyr(): BelongsTo
    {
        return $this->belongsTo(Schoolyr::class, 'schoolyr_id');
    }
}
