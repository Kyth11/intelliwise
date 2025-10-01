<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Schoolyr;
use App\Models\Subjects;
use App\Models\Rooms;
use App\Models\Section;
use App\Models\Gradelvl;
use App\Models\Faculty;

class Schedule extends Model
{
    protected $table = 'schedules';

    protected $fillable = [
        'day',
        'class_start',
        'class_end',
        'faculty_id',
        'subject_id',
        'gradelvl_id',
        'section_id',
        'room_id',
        'school_year', // IMPORTANT
    ];

    // RELATIONSHIPS
    public function schoolYear()
    {
        // local key = schedules.school_year (string), owner key = schoolyrs.school_year (string PK/unique)
        return $this->belongsTo(Schoolyr::class, 'school_year', 'school_year');
    }

    public function subject()
    {
        return $this->belongsTo(Subjects::class, 'subject_id');
    }

    public function room()
    {
        return $this->belongsTo(Rooms::class, 'room_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function gradelvl()
    {
        return $this->belongsTo(Gradelvl::class, 'gradelvl_id');
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }
}
