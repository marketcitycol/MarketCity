<?php

namespace App\Services\Payments;

use App\Models\Setting;

class PaymentRegistry
{
    /** Nombres para mostrar de medios que ya no se ofrecen o vienen del sitio anterior (pedidos históricos). */
    public const LEGACY_LABELS = [
        'contraentrega' => 'Contraentrega (histórico)',
        'cod' => 'Contraentrega (histórico)',
        'bacs' => 'Transferencia bancaria',
        'addi' => 'Addi',
        'bold_co' => 'Bold',
        'wava_payment' => 'Nequi o Daviplata (Wava)',
        'woo-mercado-pago-basic' => 'Mercado Pago',
        'epayco' => 'ePayco',
        'wompi' => 'Wompi',
        'legado' => 'Sitio anterior',
    ];

    /** @var array<string, PaymentGateway> */
    private array $gateways;

    public function __construct()
    {
        $all = [new BankTransferGateway];
        // El pago de prueba solo existe en modo demostración (MC_DEMO=true). En la tienda real no se puede activar.
        if (self::sandboxAllowed()) {
            array_unshift($all, new SandboxGateway);
        }
        $this->gateways = collect($all)->keyBy(fn ($g) => $g->key())->all();
    }

    public static function sandboxAllowed(): bool
    {
        return (bool) config('marketcity.demo');
    }

    /** @return array<string, PaymentGateway> */
    public function enabled(): array
    {
        $enabled = explode(',', (string) Setting::get('payment_methods', 'sandbox,transferencia'));
        $available = array_intersect_key($this->gateways, array_flip($enabled));
        if (isset($available['transferencia']) && ! BankTransferGateway::isConfigured()) {
            unset($available['transferencia']);
        }

        return $available;
    }

    /**
     * Medios que se pueden usar en un carrito: los activos en la plataforma que TODAS sus tiendas aceptan.
     *
     * @param  iterable<\App\Models\Store>  $stores
     * @return array<string, PaymentGateway>
     */
    public function forStores(iterable $stores): array
    {
        $available = $this->enabled();
        foreach ($stores as $store) {
            if (($allowed = $store->allowedPaymentMethods()) !== null) {
                $available = array_intersect_key($available, array_flip($allowed));
            }
        }

        return $available;
    }

    public function all(): array
    {
        return $this->gateways;
    }

    public function get(string $key): ?PaymentGateway
    {
        return $this->enabled()[$key] ?? null;
    }

    /** Nombre legible de cualquier medio, también de los históricos. */
    public static function labelFor(?string $key): string
    {
        return match ($key) {
            'sandbox' => 'Pago de prueba',
            'transferencia' => 'Transferencia bancaria',
            null, '' => '—',
            default => self::LEGACY_LABELS[$key] ?? $key,
        };
    }
}
