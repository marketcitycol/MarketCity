<?php

namespace App\Services\Content;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Mapa de cobertura sin datos de clientes (#33): un punto por ciudad a la que se ha enviado, con el
 * número de pedidos solo si son 3 o más. Nunca nombres, direcciones ni teléfonos. Las coordenadas
 * son los centros de los municipios (DIVIPOLA, datos abiertos), en resources/data.
 */
class CoverageMap
{
    public const MIN_TO_SHOW = 3;

    /** @return list<array{0:string,1:string,2:float,3:float,4:int}> [ciudad, departamento, lat, lng, pedidos (0 si son menos de 3)] */
    public static function points(): array
    {
        return Cache::remember('coverage-map:v1', now()->addHours(6), fn () => self::build());
    }

    public static function forget(): void
    {
        Cache::forget('coverage-map:v1');
    }

    private static function build(): array
    {
        [$cities, $departments] = self::coordinates();
        $points = [];
        $rows = Order::whereIn('payment_status', ['paid', 'refunded'])
            ->selectRaw('department, city, count(*) as n')->groupBy('department', 'city')->get();

        foreach ($rows as $row) {
            $dept = self::norm((string) $row->department);
            $city = str_starts_with($dept, 'bogota') ? 'bogota' : self::norm((string) $row->city);
            $hit = $cities[$city.'|'.$dept] ?? $departments[$dept] ?? null;
            if (! $hit) {
                continue;
            }
            $key = $hit[0].'|'.$hit[1];
            $points[$key] ??= [$hit[0], $hit[1], $hit[2], $hit[3], 0];
            $points[$key][4] += (int) $row->n;
        }

        if (! $points) {
            // Sitio nuevo: un punto por departamento (envíos a todo el país) sin números.
            return array_values(array_map(fn ($d) => [$d[0], $d[1], $d[2], $d[3], 0], $departments));
        }

        return array_values(array_map(function ($p) {
            $p[4] = $p[4] >= self::MIN_TO_SHOW ? $p[4] : 0;

            return $p;
        }, $points));
    }

    /** @return array{0: array<string, array>, 1: array<string, array>} ciudades por «ciudad|departamento» y primera ciudad de cada departamento */
    private static function coordinates(): array
    {
        $cities = [];
        $departments = [];
        foreach (json_decode((string) file_get_contents(resource_path('data/ciudades-coordenadas.json')), true) as $c) {
            $dept = self::norm($c[1]);
            $cities[self::norm($c[0]).'|'.$dept] ??= $c;
            $departments[$dept] ??= $c; // la lista viene de mayor a menor actividad: suele ser la capital
        }

        return [$cities, $departments];
    }

    private static function norm(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($s)));
    }
}
