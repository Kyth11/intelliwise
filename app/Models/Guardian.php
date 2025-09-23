<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    protected $table = 'guardian';

    protected $fillable = [
        'g_firstname',
        'g_middlename',
        'g_lastname',
        'g_address',
        'g_contact',
        'g_email',
        'students_id', // make sure it's fillable
    ];

    /**
     * Guardian belongs to a Student (using students_id foreign key).
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'students_id');
    }

    /**
     * Guardian has one linked User account.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'guardian_id');
    }
}
    