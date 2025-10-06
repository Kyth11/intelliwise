<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tuition extends Model
{
    protected $table = 'tuitions';

    protected $fillable = [
        'grade_level',
        // tuition
        'tuition_monthly',
        'tuition_yearly',
        // misc
        'misc_monthly',
        'misc_yearly',
        // books
        'books_desc',
        'books_amount',
        // totals + sy
        'total_yearly',
        'school_year',
    ];

    protected $casts = [
        'tuition_monthly' => 'decimal:2',
        'tuition_yearly'  => 'decimal:2',
        'misc_monthly'    => 'decimal:2',
        'misc_yearly'     => 'decimal:2',
        'books_amount'    => 'decimal:2',
        'total_yearly'    => 'decimal:2',
    ];

    /**
     * Grade-level Optional Fees attached to this tuition.
     * Pivot table: tuition_optional_fee (tuition_id, optional_fee_id)
     */
    public function optionalFees()
    {
        return $this->belongsToMany(
            OptionalFee::class,
            'tuition_optional_fee',
            'tuition_id',
            'optional_fee_id'
        )->withTimestamps();
    }
}
