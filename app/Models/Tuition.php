<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tuition extends Model
{
    protected $table = 'tuitions';

    protected $fillable = [
        'grade_level',
        'tuition_monthly','tuition_yearly',
        'misc_monthly','misc_yearly',
        'books_desc','books_amount',
        'total_yearly','school_year',
    ];

    protected $casts = [
        'tuition_monthly' => 'decimal:2',
        'tuition_yearly'  => 'decimal:2',
        'misc_monthly'    => 'decimal:2',
        'misc_yearly'     => 'decimal:2',
        'books_amount'    => 'decimal:2',
        'total_yearly'    => 'decimal:2',
    ];

    /** Grade-level Optional Fees attached to this tuition. */
    public function optionalFees(): BelongsToMany
    {
        return $this->belongsToMany(
            OptionalFee::class,
            'tuition_optional_fee',
            'tuition_id',
            'optional_fee_id'
        )->withTimestamps();
    }

    /** Payments recorded against this tuition (FK = tuition_id). */
    public function payments(): HasMany
    {
        return $this->hasMany(Payments::class, 'tuition_id');
    }
}
