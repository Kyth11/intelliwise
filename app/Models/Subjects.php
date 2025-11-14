<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subjects extends Model
{

    use SoftDeletes;

    // Table name is non-standard plural for a model; keep it explicit.
    protected $table = 'subjects';

    protected $fillable = [
        'subject_name',
        'subject_code',
        'description',
        'gradelvl_id',
        'schoolyr_id',
    ];

    /**
     * A Subject belongs to a Grade Level.
     */
    public function gradelvl(): BelongsTo
    {
        return $this->belongsTo(Gradelvl::class, 'gradelvl_id');
    }

    /**
     * A Subject can have many schedules.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'subject_id');
    }
    public function schoolyr(): BelongsTo
    {
        return $this->belongsTo(Schoolyr::class, 'schoolyr_id');
    }
}
