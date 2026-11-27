<?php

namespace App\Support;

/**
 * Entiende precios escritos en la búsqueda, como en el buscador de WordPress:
 * «audífonos menos de 80 mil», «celular hasta $1.200.000», «tenis entre 100 y 200 mil»,
 * «reloj más de 50k», «portátil por debajo de 2 millones».
 *
 * Devuelve el texto sin la parte del precio y los límites encontrados.
 */
class SearchQuery
{
    // Un número seguido de una medida («50 pulgadas», «128 GB») no es un precio.
    private const NUM = '\$?\s*(\d+(?:[.,]\d+)*)\s*(millones|millón|millon|mill|mil|k|palos|palo|lucas|luca)?(?![\p{L}\d])(?!\s*(?:pulgadas?|pulg|gb|tb|mb|mah|watts?|w|cm|mm|metros?|mts?|kg|gr|g|ml|litros?|lt|l|hz|años?|meses|días?|horas?|%|")(?![\p{L}]))';

    /** @return array{term:string, min:?int, max:?int} */
    public static function parse(string $query): array
    {
        $q = mb_strtolower(trim($query));
        $min = null;
        $max = null;

        $patterns = [
            'between' => '/\b(?:entre)\s+'.self::NUM.'\s+(?:y|a|hasta)\s+'.self::NUM.'/u',
            'max' => '/\b(?:menos\s+de|por\s+debajo\s+de|debajo\s+de|hasta|máximo|maximo|max|menor\s+a|menor\s+de|inferior\s+a|bajo)\s+'.self::NUM.'/u',
            'min' => '/\b(?:más\s+de|mas\s+de|desde|mínimo|minimo|mayor\s+a|mayor\s+de|superior\s+a|por\s+encima\s+de|encima\s+de)\s+'.self::NUM.'/u',
        ];

        if (preg_match($patterns['between'], $q, $m)) {
            // «entre 100 y 200 mil»: la unidad del segundo número vale para el primero si este no trae la suya.
            $unit = ($m[2] ?? '') !== '' ? $m[2] : ($m[4] ?? '');
            $a = self::amount($m[1], $unit);
            $b = self::amount($m[3], $m[4] ?? '');
            if ($a !== null && $b !== null) {
                [$min, $max] = [min($a, $b), max($a, $b)];
                $q = str_replace($m[0], ' ', $q);
            }
        }
        if ($max === null && preg_match($patterns['max'], $q, $m) && ($v = self::amount($m[1], $m[2] ?? '')) !== null) {
            $max = $v;
            $q = str_replace($m[0], ' ', $q);
        }
        if ($min === null && preg_match($patterns['min'], $q, $m) && ($v = self::amount($m[1], $m[2] ?? '')) !== null) {
            $min = $v;
            $q = str_replace($m[0], ' ', $q);
        }

        // Palabras de relleno que acompañan al precio y no están en los nombres de los productos.
        $q = preg_replace('/(?<![\p{L}])(?:baratos?|baratas?|económicos?|economicos?|económicas?|economicas?|pesos|cop|que\s+cuesten?|que\s+valgan?)(?![\p{L}])/u', ' ', $q);
        $term = trim(preg_replace('/\s+/', ' ', $q));
        do {
            $before = $term;
            $term = trim(preg_replace('/^(?:y|por|de|a|en|con|que|precio)(?:\s+|$)|(?:^|\s+)(?:y|por|de|a|en|con|que|precio)$/u', '', $term));
        } while ($term !== $before);
        if ($min === null && $max === null) {
            $term = trim(preg_replace('/\s+/', ' ', mb_strtolower(trim($query))));
        }

        return ['term' => $term, 'min' => $min, 'max' => $max];
    }

    /** «80», «80.000», «1,5» con su unidad → pesos. Un número sin unidad menor a 1.000 se lee en miles. */
    private static function amount(string $number, string $unit): ?int
    {
        $unit = trim($unit);
        // «1.200.000» o «80.000»: puntos o comas de miles. «1,5 millones»: coma decimal.
        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $number) && ! in_array($unit, ['millones', 'millón', 'millon', 'mill', 'palo', 'palos'], true)) {
            $value = (float) str_replace(['.', ','], '', $number);
        } else {
            $value = (float) str_replace(',', '.', $number);
        }
        $value *= match ($unit) {
            'mil', 'k', 'luca', 'lucas' => 1000,
            'millones', 'millón', 'millon', 'mill', 'palo', 'palos' => 1000000,
            default => $value < 1000 ? 1000 : 1,
        };
        $value = (int) round($value);

        return $value >= 1000 && $value <= 100000000 ? $value : null;
    }
}
