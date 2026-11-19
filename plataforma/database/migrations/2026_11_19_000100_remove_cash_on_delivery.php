<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La contraentrega no se ofrece en MarketCity (decisión del propietario, 2026-11-19).
 * Se quita de los medios activos de la plataforma y de cada comercio; en su lugar se habilita la transferencia,
 * que solo aparece cuando los datos de la cuenta están completos. Los pedidos históricos no cambian.
 */
return new class extends Migration
{
    private function clean(?string $list, bool $addTransfer): ?string
    {
        if ($list === null) {
            return null;
        }
        $items = array_values(array_filter(explode(',', $list), fn ($m) => $m !== '' && $m !== 'contraentrega'));
        if ($addTransfer && ! in_array('transferencia', $items, true)) {
            $items[] = 'transferencia';
        }

        return implode(',', $items);
    }

    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'payment_methods')->first();
        if ($row) {
            DB::table('settings')->where('key', 'payment_methods')->update(['value' => $this->clean($row->value, true)]);
        }

        foreach (DB::table('stores')->whereNotNull('payment_methods')->get(['id', 'payment_methods']) as $store) {
            $had = str_contains($store->payment_methods, 'contraentrega');
            $clean = $this->clean($store->payment_methods, $had);
            DB::table('stores')->where('id', $store->id)->update(['payment_methods' => $clean === '' ? null : $clean]);
        }
    }

    public function down(): void
    {
        // No se vuelve a ofrecer la contraentrega automáticamente.
    }
};
