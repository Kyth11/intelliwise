<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'content',
        'date_of_event', // nullable date
        'deadline',      // nullable date
        'gradelvl_id',   // nullable FK to gradelvls.id
    ];

    protected $casts = [
        'date_of_event' => 'date',
        'deadline'      => 'date',
    ];

    public function gradelvl()
    {
        return $this->belongsTo(\App\Models\Gradelvl::class, 'gradelvl_id');
    }
}
