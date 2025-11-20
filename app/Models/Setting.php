<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'judging_start',
        'judging_end',
    ];
    protected $casts = [
        'judging_start' => 'datetime',
        'judging_end' => 'datetime',
    ];
    public static function getValue($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function setValue($key, $value)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
    public static function getJudgingStatus(): string
{
    $setting = self::first();
    if (!$setting || !$setting->judging_start || !$setting->judging_end) {
        return 'open';
    }

    $now = now();

    if ($now < $setting->judging_start) {
        return 'not_started';
    }

    if ($now > $setting->judging_end) {
        return 'ended';
    }

    return 'open';
}
}
