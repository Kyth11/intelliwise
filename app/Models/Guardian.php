<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    protected $fillable = [
        'g_address','g_contact','g_email',
        'm_firstname','m_middlename','m_lastname','m_contact','m_email',
        'f_firstname','f_middlename','f_lastname','f_contact','f_email',
        'tuition_id','payment_id',
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'guardian_id');
    }


    public function user()
    {
        return $this->hasOne(User::class, 'guardian_id');
    }
}
