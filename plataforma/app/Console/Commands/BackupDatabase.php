<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Respaldo de la base sin consola ni mysqldump: exporta cada tabla a un archivo JSON comprimido
 * en storage/app/respaldos. Sirve para SQLite y MySQL. Lo usa la actualización automática.
 */
class BackupDatabase extends Command
{
    protected $signature = 'mc:respaldar {--conservar=10 : Cuántos respaldos guardar}';

    protected $description = 'Exporta la base de datos a storage/app/respaldos (JSON comprimido)';

    public function handle(): int
    {
        $dir = storage_path('app/respaldos');
        is_dir($dir) || mkdir($dir, 0750, true);
        $file = $dir.'/base-'.now()->format('Ymd-His').'.json.gz';
        $gz = gzopen($file, 'wb6');
        gzwrite($gz, '{"creado":"'.now()->toIso8601String().'","conexion":"'.config('database.default').'","tablas":{');
        $first = true;
        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            if (in_array($table, ['sessions', 'cache', 'cache_locks'], true)) {
                continue;
            }
            gzwrite($gz, ($first ? '' : ',').json_encode($table).':[');
            $firstRow = true;
            $order = Schema::getColumnListing($table)[0];
            DB::table($table)->orderBy($order)->chunk(500, function ($rows) use ($gz, &$firstRow) {
                foreach ($rows as $row) {
                    gzwrite($gz, ($firstRow ? '' : ',').json_encode($row, JSON_UNESCAPED_UNICODE));
                    $firstRow = false;
                }
            });
            gzwrite($gz, ']');
            $first = false;
        }
        gzwrite($gz, '}}');
        gzclose($gz);

        $all = glob($dir.'/base-*.json.gz');
        rsort($all);
        foreach (array_slice($all, max(1, (int) $this->option('conservar'))) as $old) {
            @unlink($old);
        }
        $this->info('Respaldo: '.$file);

        return self::SUCCESS;
    }
}
