<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tuition extends Model
{
    use SoftDeletes;

    protected $table = 'tuitions';

    protected $fillable = [
        'grade_level',
        'tuition_monthly',
        'tuition_yearly',
        'misc_monthly',
        'misc_yearly',
        'books_desc',
        'books_amount',
        'registration_fee',
        'total_yearly',
        'school_year',
        'schoolyr_id',
    ];

    protected $casts = [
        'tuition_monthly'  => 'decimal:2',
        'tuition_yearly'   => 'decimal:2',
        'misc_monthly'     => 'decimal:2',
        'misc_yearly'      => 'decimal:2',
        'books_amount'     => 'decimal:2',
        'registration_fee' => 'decimal:2',
        'total_yearly'     => 'decimal:2',
    ];

    public function optionalFees(): BelongsToMany
    {
        return $this->belongsToMany(
            OptionalFee::class,
            'tuition_optional_fee',
            'tuition_id',
            'optional_fee_id'
        )->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payments::class, 'tuition_id');
    }

    public function schoolyr(): BelongsTo
    {
        return $this->belongsTo(Schoolyr::class, 'schoolyr_id');
    }
}
