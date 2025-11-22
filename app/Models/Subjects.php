<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subjects extends Model
{
    use SoftDeletes;

    protected $table = 'subjects';

    protected $fillable = [
        'subject_name',
        'gradelvl_id',
    ];

    public function gradelvl(): BelongsTo
    {
        return $this->belongsTo(Gradelvl::class, 'gradelvl_id');
    }
    
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'subject_id');
    }
    public function schoolyr(): BelongsTo
    {
        return $this->belongsTo(Schoolyr::class, 'schoolyr_id');
    }
}
