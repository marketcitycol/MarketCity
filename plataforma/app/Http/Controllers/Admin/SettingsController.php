<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MerchantEntity;
use App\Models\Setting;
use App\Services\Payments\PaymentRegistry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index(PaymentRegistry $payments)
    {
        return view('admin.settings', [
            'entities' => MerchantEntity::orderByDesc('is_active')->orderBy('id')->get(),
            'gateways' => $payments->all(),
            'enabled' => explode(',', (string) Setting::get('payment_methods', '')),
            's' => fn ($k, $d = null) => Setting::get($k, $d),
        ]);
    }

    public function update(Request $request, PaymentRegistry $payments)
    {
        $data = $request->validate([
            'commission_percent' => 'required|numeric|min:0|max:100',
            'commission_fixed' => 'required|integer|min:0|max:1000000',
            'settlement_business_days' => 'required|integer|min:0|max:60',
            'installments' => 'required|integer|min:1|max:48',
            'terms_version' => 'required|string|max:20',
            'payment_methods' => 'required|array|min:1',
            'payment_methods.*' => [Rule::in(array_keys($payments->all()))],
        ]);
        $data['payment_methods'] = implode(',', $data['payment_methods']);
        foreach ($data as $k => $v) {
            Setting::put($k, (string) $v);
        }

        return back()->with('success', 'Configuración guardada. Aplica a los pedidos nuevos; los anteriores conservan su regla.');
    }

    /** Datos de la cuenta para transferencias. La transferencia solo se ofrece cuando están todos. */
    public function updateTransfer(Request $request)
    {
        $data = $request->validate([
            'transfer_bank' => 'nullable|string|max:60',
            'transfer_account_type' => ['nullable', Rule::in(['Ahorros', 'Corriente'])],
            'transfer_account_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9 \-]+$/'],
            'transfer_holder' => 'nullable|string|max:120',
            'transfer_holder_document' => 'nullable|string|max:40',
            'transfer_instructions' => 'nullable|string|max:500',
            'transfer_deadline_hours' => 'required|integer|min:1|max:168',
        ], ['transfer_account_number.regex' => 'El número de cuenta solo lleva números, espacios o guiones.']);
        foreach ($data as $k => $v) {
            Setting::put($k, trim((string) $v));
        }

        return back()->with('success', \App\Services\Payments\BankTransferGateway::isConfigured()
            ? 'Datos de transferencia guardados. Ya se ofrece en el checkout si está activa en «Medios de pago».'
            : 'Guardado. Faltan datos de la cuenta: la transferencia no se ofrece hasta completarlos.');
    }

    public function storeEntity(Request $request)
    {
        $data = $request->validate([
            'legal_name' => 'required|string|max:150',
            'person_type' => ['required', Rule::in(['natural', 'juridica'])],
            'document_type' => ['required', Rule::in(['CC', 'NIT'])],
            'document_number' => 'required|string|max:30',
            'email' => 'nullable|email',
            'city' => 'nullable|string|max:80',
        ]);
        MerchantEntity::create($data + ['is_active' => false]);

        return back()->with('success', 'Titular agregado. Actívalo cuando las pasarelas estén a su nombre.');
    }

    public function activateEntity(MerchantEntity $entity)
    {
        $entity->activate();

        return back()->with('success', "Titular vigente: {$entity->legal_name}. Los pedidos nuevos quedan a su nombre.");
    }
}
