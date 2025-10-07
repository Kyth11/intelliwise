<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    protected $fillable = [
        // ✅ Guardian (g_*) — used by your controller
        'g_firstname','g_middlename','g_lastname',
        'g_address','g_contact','g_email',

        // (If you still store mother/father info, keep them too)
        'm_firstname','m_middlename','m_lastname','m_contact','m_email',
        'f_firstname','f_middlename','f_lastname','f_contact','f_email',

        // Payment links
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

