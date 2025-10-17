<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Gradelvl extends Model
{
    protected $table = 'gradelvls';

    protected $fillable = [
        'grade_level', // e.g., "Grade 1", "G7", etc.
    ];

    /**
     * Subjects that belong to this grade level.
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subjects::class, 'gradelvl_id');
    }

    /**
     * Schedules under this grade level.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'gradelvl_id');
    }

    /**
     * Announcements targeted to this grade level (many-to-many).
     */
    public function announcements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class, 'announcement_gradelvl', 'gradelvl_id', 'announcement_id')
            ->withTimestamps();
    }
}
