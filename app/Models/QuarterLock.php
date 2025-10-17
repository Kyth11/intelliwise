<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuarterLock extends Model
{
    protected $fillable = ['scope', 'q1', 'q2', 'q3', 'q4'];

    protected $casts = [
        'q1' => 'boolean',
        'q2' => 'boolean',
        'q3' => 'boolean',
        'q4' => 'boolean',
    ];

    public static function globalRow(): self
    {
        return static::firstOrCreate(
            ['scope' => 'GLOBAL'],
            ['q1' => true, 'q2' => true, 'q3' => true, 'q4' => true]
        );
    }

    /**
     * Always returns the persisted GLOBAL flags.
     */
    public static function flags($schoolyrId = null, $gradeLevel = null): array
    {
        $row = static::globalRow();
        return [
            'q1' => (bool)$row->q1,
            'q2' => (bool)$row->q2,
            'q3' => (bool)$row->q3,
            'q4' => (bool)$row->q4,
        ];
    }

    public static function setGlobal(array $data): void
    {
        $row = static::globalRow();
        $row->fill([
            'q1' => (bool)($data['q1'] ?? false),
            'q2' => (bool)($data['q2'] ?? false),
            'q3' => (bool)($data['q3'] ?? false),
            'q4' => (bool)($data['q4'] ?? false),
        ])->save();
    }
    
}
