<?php

namespace App\Console\Commands;

use App\Legacy\WooCommerceCsvImporter;
use App\Models\Store;
use Illuminate\Console\Command;

class ImportWooCommerce extends Command
{
    protected $signature = 'mc:importar-catalogo {archivo : CSV exportado desde WooCommerce} {--tienda= : slug de la tienda por defecto}';

    protected $description = 'Importa productos desde la exportación CSV de WooCommerce (repetible, sin duplicar).';

    public function handle(WooCommerceCsvImporter $importer): int
    {
        $store = $this->option('tienda')
            ? Store::where('slug', $this->option('tienda'))->firstOrFail()
            : Store::orderBy('id')->firstOrFail();

        $stats = $importer->import($this->argument('archivo'), $store);
        $this->info("Creados: {$stats['created']} · Actualizados: {$stats['updated']} · Omitidos: {$stats['skipped']} · Tiendas nuevas: {$stats['stores']}");

        return self::SUCCESS;
    }
}
