<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        's_firstname','s_middlename','s_lastname',
        's_birthdate','s_address','s_citizenship','s_religion',
        's_contact','s_email',
        'guardian_id',
        's_gradelvl','gradelvl_id',
        's_tuition_sum','tuition_id',
        's_optional_total','s_total_due', // include if you’ll mass-assign
        'enrollment_status','payment_status',
    ];

    protected $casts = [
        's_optional_total' => 'decimal:2',
        's_total_due'      => 'decimal:2',
    ];

    public function guardian()
    {
        return $this->belongsTo(Guardian::class, 'guardian_id');
    }

    public function tuition()
    {
        return $this->belongsTo(Tuition::class);
    }

    public function optionalFees()
    {
        return $this->belongsToMany(OptionalFee::class, 'student_optional_fees')
            ->withPivot('amount_override')
            ->withTimestamps();
    }
    public function getFullNameAttribute(): string
{
    $mid = trim((string)($this->s_middlename ?? ''));
    $name = trim(implode(' ', array_filter([$this->s_firstname ?? '', $mid, $this->s_lastname ?? ''])));
    return $name !== '' ? $name : ('Student #'.$this->id);
}
}
