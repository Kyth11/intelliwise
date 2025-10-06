<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionalFee extends Model
{
    protected $fillable = ['name', 'amount', 'active'];

    protected $casts = [
        'active' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function tuitions()
    {
        return $this->belongsToMany(Tuition::class, 'tuition_optional_fee')->withTimestamps();
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_optional_fees')
            ->withPivot('amount_override')
            ->withTimestamps();
    }
}
