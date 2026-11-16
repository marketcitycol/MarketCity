<?php

namespace App\Services\Payments;

use App\Models\Setting;

class PaymentRegistry
{
    /** @var array<string, PaymentGateway> */
    private array $gateways;

    public function __construct()
    {
        $all = [new SandboxGateway, new CashOnDeliveryGateway];
        $this->gateways = collect($all)->keyBy(fn ($g) => $g->key())->all();
    }

    /** @return array<string, PaymentGateway> */
    public function enabled(): array
    {
        $enabled = explode(',', (string) Setting::get('payment_methods', 'sandbox,contraentrega'));

        return array_intersect_key($this->gateways, array_flip($enabled));
    }

    public function all(): array
    {
        return $this->gateways;
    }

    public function get(string $key): ?PaymentGateway
    {
        return $this->enabled()[$key] ?? null;
    }
}
