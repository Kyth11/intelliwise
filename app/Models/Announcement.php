<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'content',
        'date_of_event',
        'deadline',
        'gradelvl_id', // legacy single selection
    ];

    protected $casts = [
        'date_of_event' => 'date',
        'deadline'      => 'date',
    ];

    /**
     * Legacy single grade level (nullable).
     */
    public function gradelvl(): BelongsTo
    {
        return $this->belongsTo(Gradelvl::class, 'gradelvl_id');
    }

    /**
     * New: many-to-many grade levels via pivot table "announcement_gradelvl".
     */
    public function gradelvls(): BelongsToMany
    {
        return $this->belongsToMany(Gradelvl::class, 'announcement_gradelvl', 'announcement_id', 'gradelvl_id')
            ->withTimestamps();
    }

    /**
     * Helper: "Grade 1, Grade 2, ..." from the many-to-many relation.
     */
    public function getGradeLevelNamesAttribute(): string
    {
        // Eager-load gradelvls in queries to avoid N+1 when using this accessor.
        return $this->gradelvls->pluck('grade_level')->implode(', ');
    }
}
