<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'content',
        'date_of_event',
        'deadline',
        'gradelvl_id',   // keep for legacy UI (first selected)
    ];

    protected $casts = [
        'date_of_event' => 'date',
        'deadline'      => 'date',
    ];

    // Legacy single grade level (for older UIs that expect one)
    public function gradelvl()
    {
        return $this->belongsTo(\App\Models\Gradelvl::class, 'gradelvl_id');
    }

    // NEW: Many-to-many grades
    public function gradelvls()
    {
        return $this->belongsToMany(\App\Models\Gradelvl::class, 'announcement_gradelvl')
            ->withTimestamps();
    }

    // Helper (optional): comma-separated names of grade levels
    public function getGradeLevelNamesAttribute(): string
    {
        return $this->gradelvls->pluck('grade_level')->implode(', ');
    }
}
