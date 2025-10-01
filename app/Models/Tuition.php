<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tuition extends Model
{
    protected $table = 'tuitions';

    // Option A:
    // protected $guarded = [];

    // Option B (explicit):
    protected $fillable = [
        'grade_level',
        'monthly_fee',
        'yearly_fee',
        'misc_fee',
        'optional_fee_desc',
        'optional_fee_amount',
        'total_yearly',
        'school_year',   // ← IMPORTANT
    ];
}
