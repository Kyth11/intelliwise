<?php

namespace App\Models;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guardian extends Model
{

     use SoftDeletes;

    protected $fillable = [

        'name','address','contact','email','relation','schoolyr_id',
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
    public function schoolyr(): BelongsTo
    {
        return $this->belongsTo(Schoolyr::class, 'schoolyr_id');
    }
}


