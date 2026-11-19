<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pasa los datos de la versión 1 desde el archivo SQLite a la base configurada (MySQL/MariaDB de Hostinger).
 * No borra el archivo SQLite: queda como respaldo. Se niega a copiar sobre una base que ya tiene datos.
 */
class CopySqliteToDatabase extends Command
{
    protected $signature = 'mc:copiar-sqlite {--desde= : Ruta del archivo SQLite (por defecto database/database.sqlite)} {--force : Copiar aunque el destino ya tenga datos}';

    protected $description = 'Copia todos los datos del archivo SQLite de v1 a la base MySQL configurada';

    /** Tablas técnicas que no hace falta llevar (se regeneran solas). */
    private const SKIP = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

    /** Orden de copia: primero lo que otras tablas referencian. */
    private const ORDER = ['users', 'merchant_entities', 'settings', 'stores', 'categories', 'brands', 'products', 'pages', 'orders', 'seller_orders', 'order_items', 'order_events', 'payments'];

    public function handle(): int
    {
        $target = config('database.default');
        if (config("database.connections.$target.driver") === 'sqlite') {
            $this->error('La base configurada sigue siendo SQLite. Cambie DB_CONNECTION a mysql en el .env antes de copiar.');

            return self::FAILURE;
        }
        $file = $this->option('desde') ?: database_path('database.sqlite');
        if (! is_file($file)) {
            $this->error("No existe el archivo SQLite: $file");

            return self::FAILURE;
        }

        config(['database.connections.origen_sqlite' => ['driver' => 'sqlite', 'database' => $file, 'prefix' => '', 'foreign_key_constraints' => false]]);
        $source = DB::connection('origen_sqlite');
        $dest = DB::connection();

        $sourceTables = collect($source->select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'"))->pluck('name')->all();
        $tables = array_values(array_diff(array_intersect($sourceTables, Schema::getTableListing(schemaQualified: false)), self::SKIP));
        usort($tables, fn ($a, $b) => (array_search($a, self::ORDER, true) === false ? 99 : array_search($a, self::ORDER, true))
            <=> (array_search($b, self::ORDER, true) === false ? 99 : array_search($b, self::ORDER, true)));

        if (! $this->option('force')) {
            foreach (['users', 'products', 'orders'] as $t) {
                if (in_array($t, $tables, true) && $dest->table($t)->exists()) {
                    $this->error("La base de destino ya tiene datos en «{$t}». No se copió nada.");

                    return self::FAILURE;
                }
            }
        }

        $counts = [];
        Schema::disableForeignKeyConstraints();
        try {
            $dest->transaction(function () use ($tables, $source, $dest, &$counts) {
                foreach ($tables as $table) {
                    $columns = array_values(array_intersect(Schema::connection('origen_sqlite')->getColumnListing($table), Schema::getColumnListing($table)));
                    $dest->table($table)->delete();
                    $n = 0;
                    $source->table($table)->select($columns)->orderBy($columns[0])->chunk(200, function ($rows) use ($dest, $table, &$n) {
                        $dest->table($table)->insert($rows->map(fn ($r) => (array) $r)->all());
                        $n += $rows->count();
                    });
                    $counts[$table] = $n;
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        foreach ($counts as $table => $n) {
            $now = $dest->table($table)->count();
            $this->line(sprintf('%-18s %6d filas %s', $table, $n, $now === $n ? 'OK' : "DIFERENCIA (destino: $now)"));
            if ($now !== $n) {
                return self::FAILURE;
            }
        }
        $this->info('Copia completa y verificada. El archivo SQLite se conserva como respaldo.');

        return self::SUCCESS;
    }
}
