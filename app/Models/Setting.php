<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'competition_id',
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
    public static function getJudgingStatus(?int $competitionId = null): string
    {
        $now = now();
        $start = null;
        $end = null;

        // Helper to extract dates from a setting row
        $extractDates = function ($setting) use (&$start, &$end) {
            if ($setting && $setting->judging_start && $setting->judging_end) {
                $start = $setting->judging_start;
                $end = $setting->judging_end;
                return true;
            }
            return false;
        };

        // 1. Try specific competition setting
        if ($competitionId) {
            // Try with the standard key
            $setting = self::where('competition_id', $competitionId)->where('key', 'judging_period')->first();
            if (!$extractDates($setting)) {
                // Fallback: any row for this competition (if key mismatch)
                $setting = self::where('competition_id', $competitionId)->whereNotNull('judging_start')->first();
                $extractDates($setting);
            }
        }

        // 2. If not found, try global setting (competition_id is null)
        if (!$start || !$end) {
            $setting = self::whereNull('competition_id')->where('key', 'judging_period')->first();
            if (!$extractDates($setting)) {
                // Fallback: legacy global row (first row, or null competition_id)
                $setting = self::whereNull('competition_id')->whereNotNull('judging_start')->first();
                if (!$extractDates($setting)) {
                    // Absolute fallback to first row (legacy)
                    $setting = self::first();
                    $extractDates($setting);
                }
            }
        }

        // If still no dates, assume open or not started? 
        // If dates are missing, we can't enforce, so maybe 'open'?
        if (!$start || !$end) {
            return 'open';
        }

        if ($now < $start) {
            return 'not_started';
        }

        if ($now > $end) {
            return 'ended';
        }

        return 'open';
    }
}
