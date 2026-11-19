<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Días hábiles en Colombia: lunes a viernes sin festivos (Ley 51 de 1983, "Ley Emiliani").
 * Se usa para los plazos de PQRS (15 días hábiles) y del derecho de retracto (5 días hábiles).
 */
class ColombiaCalendar
{
    public const TZ = 'America/Bogota';

    /** @var array<int, array<string, true>> */
    private static array $cache = [];

    /** @return array<string, true> fechas Y-m-d festivas del año */
    public static function holidays(int $year): array
    {
        if (isset(self::$cache[$year])) {
            return self::$cache[$year];
        }

        $d = fn (int $m, int $day) => CarbonImmutable::create($year, $m, $day, 0, 0, 0, self::TZ);
        $nextMonday = fn (CarbonImmutable $x) => $x->isMonday() ? $x : $x->next(CarbonInterface::MONDAY);
        $easter = self::easter($year);

        $days = [
            $d(1, 1), $d(5, 1), $d(7, 20), $d(8, 7), $d(12, 8), $d(12, 25),
            // Trasladables al lunes siguiente
            $nextMonday($d(1, 6)), $nextMonday($d(3, 19)), $nextMonday($d(6, 29)), $nextMonday($d(8, 15)),
            $nextMonday($d(10, 12)), $nextMonday($d(11, 1)), $nextMonday($d(11, 11)),
            // Según la Pascua
            $easter->subDays(3), $easter->subDays(2), $easter->addDays(43), $easter->addDays(64), $easter->addDays(71),
        ];

        $out = [];
        foreach ($days as $day) {
            $out[$day->format('Y-m-d')] = true;
        }

        return self::$cache[$year] = $out;
    }

    public static function isBusinessDay(CarbonInterface $date): bool
    {
        $date = CarbonImmutable::instance($date)->setTimezone(self::TZ);

        return ! $date->isWeekend() && ! isset(self::holidays($date->year)[$date->format('Y-m-d')]);
    }

    /** Suma días hábiles; el día de inicio no cuenta. Devuelve el final de ese día. */
    public static function addBusinessDays(CarbonInterface $from, int $days): CarbonImmutable
    {
        $date = CarbonImmutable::instance($from)->setTimezone(self::TZ)->startOfDay();
        while ($days > 0) {
            $date = $date->addDay();
            if (self::isBusinessDay($date)) {
                $days--;
            }
        }

        return $date->endOfDay();
    }

    /** Domingo de Pascua (algoritmo gregoriano anónimo). */
    public static function easter(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day, 0, 0, 0, self::TZ);
    }
}
