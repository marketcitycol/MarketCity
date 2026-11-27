<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\MarketingConsent;
use App\Models\Setting;
use App\Services\Marketing\AbandonedCarts;
use App\Services\Marketing\MessagingPermissions;
use Illuminate\Http\Request;

/** Backoffice → Mensajes: permisos de mensajes (#82) y carritos abandonados (#83). */
class MessagesController extends Controller
{
    public function index(AbandonedCarts $carts)
    {
        $rows = AbandonedCart::latest('id')->limit(100)->get();

        return view('admin.messages.index', [
            'mode' => AbandonedCarts::mode(),
            'settings' => [
                'test_emails' => Setting::get('abandoned_cart_test_emails', ''),
                'third' => (bool) Setting::get('abandoned_cart_third'),
                'email_hour_cap' => (int) Setting::get('marketing_email_hour_cap', 40),
                'email_day_cap' => (int) Setting::get('marketing_email_day_cap', 300),
                'whatsapp_day_cap' => (int) Setting::get('marketing_whatsapp_day_cap', 100),
            ],
            'stats' => [
                'abiertos' => AbandonedCart::where('status', 'open')->count(),
                'recuperados' => AbandonedCart::where('status', 'recovered')->count(),
                'valor_recuperado' => (int) AbandonedCart::where('status', 'recovered')->sum('total'),
                'autorizaciones' => MarketingConsent::where('granted', true)->count(),
                'bajas' => MarketingConsent::where('granted', false)->count(),
            ],
            'carts' => $rows,
            'whatsapp' => $rows->mapWithKeys(fn ($r) => [$r->id => $r->channel === 'whatsapp' && $r->status === 'open' ? $carts->whatsappLink($r) : null]),
            'consents' => MarketingConsent::latest('id')->limit(200)->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'mode' => 'required|in:off,test,public',
            'test_emails' => 'nullable|string|max:1000',
            'third' => 'nullable|boolean',
            'email_hour_cap' => 'required|integer|min:1|max:1000',
            'email_day_cap' => 'required|integer|min:1|max:10000',
            'whatsapp_day_cap' => 'required|integer|min:1|max:10000',
        ]);
        Setting::put('abandoned_cart_mode', $data['mode']);
        Setting::put('abandoned_cart_test_emails', $data['test_emails'] ?? '');
        Setting::put('abandoned_cart_third', ! empty($data['third']) ? '1' : '');
        Setting::put('marketing_email_hour_cap', $data['email_hour_cap']);
        Setting::put('marketing_email_day_cap', $data['email_day_cap']);
        Setting::put('marketing_whatsapp_day_cap', $data['whatsapp_day_cap']);

        return back()->with('success', 'Ajustes de mensajes guardados.');
    }

    /** El equipo envió el WhatsApp a mano desde el panel: cuenta como contacto del día. */
    public function whatsappSent(AbandonedCart $cart, MessagingPermissions $permissions, AbandonedCarts $carts)
    {
        $why = $permissions->whyNot($cart->email, $cart->phone, 'whatsapp');
        if (! $permissions->allows($cart->phone, 'whatsapp', 'carrito') || $why !== '') {
            return back()->with('error', 'Hoy no se le puede escribir por WhatsApp ('.($why ?: 'sin permiso').').');
        }
        $permissions->logContact($cart->email, $cart->phone, 'whatsapp', 'carrito-manual', (string) $cart->id);
        $carts->advance($cart, true);

        return back()->with('success', 'Registrado el WhatsApp enviado.');
    }

    public function revoke(Request $request, MessagingPermissions $permissions)
    {
        $data = $request->validate(['contact' => 'required|string|max:120']);
        $isEmail = str_contains($data['contact'], '@');
        $permissions->revoke($isEmail ? $data['contact'] : null, $isEmail ? null : $data['contact'], 'todo', 'backoffice');

        return back()->with('success', 'Baja registrada.');
    }

    /** Registro de autorizaciones y bajas en CSV (sin fórmulas: los valores que empiezan por =+-@ van con apóstrofo). */
    public function csv()
    {
        $safe = fn ($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'".$v : (string) $v;

        return response()->streamDownload(function () use ($safe) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['fecha_utc', 'contacto', 'canal', 'finalidad', 'autoriza', 'origen', 'version_texto', 'huella_texto', 'ip_cifrada', 'navegador', 'pedido']);
            MarketingConsent::orderBy('id')->chunk(500, function ($rows) use ($out, $safe) {
                foreach ($rows as $r) {
                    fputcsv($out, array_map($safe, [$r->created_at?->toIso8601String(), $r->contact, $r->channel, $r->purpose, $r->granted ? 'si' : 'no', $r->source, $r->text_version, $r->text_hash, $r->ip_hash, $r->user_agent, $r->order_id]));
                }
            });
            fclose($out);
        }, 'permisos-de-mensajes.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
