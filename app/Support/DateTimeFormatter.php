<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;
use DateTimeZone;
use Exception;

final class DateTimeFormatter
{
    public static function inDisplayTimezone(?DateTimeInterface $date): ?Carbon
    {
        return $date === null ? null : self::convert($date);
    }

    public static function iso(DateTimeInterface $date): string
    {
        return self::convert($date)->toISOString(true);
    }

    public static function nullableIso(?DateTimeInterface $date): ?string
    {
        return $date === null ? null : self::iso($date);
    }

    public static function format(?DateTimeInterface $date, string $format): ?string
    {
        return self::inDisplayTimezone($date)?->format($format);
    }

    private static function convert(DateTimeInterface $date): Carbon
    {
        return Carbon::instance($date)->setTimezone(self::displayTimezone());
    }

    private static function displayTimezone(): DateTimeZone
    {
        // India-only: Asia/Kolkata comes from config/regional.php.
        $regional = config('regional.timezone', 'Asia/Kolkata');
        $timezone = config('app.display_timezone', $regional);

        try {
            return new DateTimeZone(is_string($timezone) ? $timezone : $regional);
        } catch (Exception) {
            return new DateTimeZone($regional);
        }
    }
}
