<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'school_year', // references Schoolyr::school_year (string key)
    ];

    /**
     * Optional: cast time strings if you want Carbon instances.
     * (Uncomment if you store full datetime; keep as string if HH:MM)
     */
    // protected $casts = [
    //     'class_start' => 'datetime:H:i',
    //     'class_end'   => 'datetime:H:i',
    // ];

    /**
     * School year relation keyed by human-readable "school_year" (e.g., "2025-2026").
     * Local key: schedules.school_year, Owner key: schoolyrs.school_year
     */
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(Schoolyr::class, 'school_year', 'school_year');
    }

    /**
     * Subject offered on this schedule.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subjects::class, 'subject_id');
    }

    /**
     * Grade level for this class.
     */
    public function gradelvl(): BelongsTo
    {
        return $this->belongsTo(Gradelvl::class, 'gradelvl_id');
    }

    /**
     * Assigned faculty (teacher/instructor).
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }
}
