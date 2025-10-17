<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';
    protected $fillable = ['key','value'];
    protected $casts = ['value' => 'array'];

    public static function get(string $key, $default = null)
    {
        $row = static::query()->where('key', $key)->first();
        if (!$row) return $default;

        $val = $row->value;
        return is_array($val) && array_key_exists('v', $val) ? $val['v'] : $val;
    }

    public static function set(string $key, $value)
    {
        $payload = is_array($value) ? $value : ['v' => $value];
        return static::updateOrCreate(['key' => $key], ['value' => $payload]);
    }
}
