<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        's_firstname',
        's_middlename',
        's_lastname',
        's_birthdate',
        's_address',
        's_contact',
        's_email',
        'guardian_id',
        's_gradelvl',
        'gradelvl_id',
        's_tuition_sum',
        'tuition_id',
        'enrollment_status',
        'payment_status'
    ];

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }
    public function tuition()
    {
        return $this->belongsTo(Tuition::class);
    }
}
