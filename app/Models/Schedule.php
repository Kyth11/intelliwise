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
        'subject_id',   // IMPORTANT: must be here
        'gradelvl_id',
        'school_year',
    ];

    /**
     * School year relation keyed by human-readable "school_year" (e.g., "2025-2026").
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
