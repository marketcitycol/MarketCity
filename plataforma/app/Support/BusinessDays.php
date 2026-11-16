<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Días hábiles en Colombia (lunes a viernes sin festivos).
 * Festivos según la Ley 51 de 1983 ("Ley Emiliani") y los que dependen de la Pascua.
 */
class BusinessDays
{
    /** @var array<int, array<string, true>> */
    private static array $cache = [];

    public static function add(DateTimeInterface $from, int $days): CarbonImmutable
    {
        $date = CarbonImmutable::instance($from)->setTimezone('America/Bogota')->startOfDay();
        while ($days > 0) {
            $date = $date->addDay();
            if (self::isBusinessDay($date)) {
                $days--;
            }
        }

        return $date;
    }

    public static function isBusinessDay(DateTimeInterface $date): bool
    {
        $d = CarbonImmutable::instance($date);

        return ! $d->isWeekend() && ! isset(self::holidays((int) $d->year)[$d->format('Y-m-d')]);
    }

    /** @return array<string, true> */
    public static function holidays(int $year): array
    {
        if (isset(self::$cache[$year])) {
            return self::$cache[$year];
        }

        $fixed = ["$year-01-01", "$year-05-01", "$year-07-20", "$year-08-07", "$year-12-08", "$year-12-25"];
        // Se trasladan al lunes siguiente (Ley Emiliani).
        $movable = ["$year-01-06", "$year-03-19", "$year-06-29", "$year-08-15", "$year-10-12", "$year-11-01", "$year-11-11"];

        $easter = self::easter($year);
        $days = $fixed;
        $days[] = $easter->subDays(3)->format('Y-m-d'); // Jueves Santo
        $days[] = $easter->subDays(2)->format('Y-m-d'); // Viernes Santo
        foreach ([43, 64, 71] as $offset) { // Ascensión, Corpus Christi, Sagrado Corazón (ya en lunes)
            $days[] = $easter->addDays($offset)->format('Y-m-d');
        }
        foreach ($movable as $m) {
            $d = CarbonImmutable::parse($m);
            $days[] = ($d->isMonday() ? $d : $d->next(CarbonImmutable::MONDAY))->format('Y-m-d');
        }

        return self::$cache[$year] = array_fill_keys($days, true);
    }

    /** Domingo de Pascua (algoritmo gregoriano anónimo). */
    private static function easter(int $y): CarbonImmutable
    {
        $a = $y % 19; $b = intdiv($y, 100); $c = $y % 100; $d = intdiv($b, 4); $e = $b % 4;
        $f = intdiv($b + 8, 25); $g = intdiv($b - $f + 1, 3); $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4); $k = $c % 4; $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($y, $month, $day);
    }
}
