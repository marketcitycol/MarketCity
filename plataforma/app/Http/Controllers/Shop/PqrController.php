<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\PqrNoticeMail;
use App\Mail\PqrReceivedMail;
use App\Models\Order;
use App\Models\Pqr;
use App\Models\User;
use App\Support\ColombiaCalendar;
use App\Support\Mailer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** PQRS públicas: radicar y consultar con radicado + correo. */
class PqrController extends Controller
{
    public function create(Request $request)
    {
        $type = array_key_exists((string) $request->query('tipo'), Pqr::TYPES) ? $request->query('tipo') : 'peticion';

        return view('shop.pqrs.create', ['type' => $type, 'orderNumber' => $request->query('pedido')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Pqr::TYPES))],
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160',
            'phone' => 'nullable|string|max:30',
            'document_number' => 'nullable|string|max:20',
            'order_number' => [Rule::requiredIf(in_array($request->input('type'), ['retracto', 'reversion'], true)), 'nullable', 'string', 'max:20'],
            'message' => 'required|string|min:10|max:5000',
            'data_consent' => 'accepted',
            'website' => 'prohibited', // trampa para bots
        ], [
            'order_number.required' => 'Para retracto o reversión del pago escribe el número de tu pedido.',
            'data_consent.accepted' => 'Necesitamos tu autorización para tratar tus datos y responderte.',
        ]);

        $order = null;
        if (! empty($data['order_number'])) {
            $order = Order::with('sellerOrders')->where('number', strtoupper(trim($data['order_number'])))
                ->whereRaw('lower(email) = ?', [mb_strtolower($data['email'])])->first();
            if (! $order && in_array($data['type'], ['retracto', 'reversion'], true)) {
                return back()->withInput()->with('error', 'No encontramos un pedido con ese número y ese correo. Usa el correo con el que compraste.');
            }
        }

        $withinTerm = null;
        if ($order && $data['type'] === 'retracto') {
            $withinTerm = Pqr::retractWithinTerm($order);
        }

        $attrs = [
            'type' => $data['type'],
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'order_number' => $order?->number ?? (isset($data['order_number']) ? strtoupper(trim($data['order_number'])) : null),
            'order_id' => $order?->id,
            'user_id' => $request->user()?->id,
            'message' => $data['message'],
            'within_term' => $withinTerm,
            'due_at' => ColombiaCalendar::addBusinessDays(now(), Pqr::RESPONSE_DAYS),
            'data_consent_at' => now(),
            'ip' => $request->ip(),
        ];

        $pqr = null;
        for ($try = 0; $try < 3 && ! $pqr; $try++) {
            try {
                $pqr = Pqr::create($attrs + ['radicado' => Pqr::nextRadicado()]);
            } catch (UniqueConstraintViolationException) {
                // Otro radicado salió al mismo tiempo: se vuelve a numerar.
            }
        }
        abort_unless($pqr, 503, 'No pudimos radicar tu solicitud. Intenta de nuevo en un momento.');

        if ($order) {
            $order->log('pqrs', "{$pqr->typeLabel()} radicada: {$pqr->radicado}.");
        }

        Mailer::send($pqr->email, new PqrReceivedMail($pqr));
        foreach (User::where('role', User::ROLE_ADMIN)->pluck('email') as $admin) {
            Mailer::send($admin, new PqrNoticeMail($pqr, forSeller: false));
        }
        if ($order && in_array($pqr->type, Pqr::ORDER_TYPES, true)) {
            foreach ($order->sellerOrders as $so) {
                Mailer::send($so->store?->notificationEmail(), new PqrNoticeMail($pqr, forSeller: true, store: $so->store));
            }
        }

        return redirect()->route('pqrs.status')->with('pqr_radicado', $pqr->radicado)->with('pqr_email', $pqr->email);
    }

    public function status(Request $request)
    {
        $radicado = session('pqr_radicado');
        $pqr = $radicado ? Pqr::where('radicado', $radicado)->where('email', session('pqr_email'))->first() : null;

        return view('shop.pqrs.status', ['pqr' => $pqr, 'justFiled' => (bool) $pqr]);
    }

    public function lookup(Request $request)
    {
        $data = $request->validate(['radicado' => 'required|string|max:24', 'email' => 'required|email']);
        $pqr = Pqr::where('radicado', strtoupper(trim($data['radicado'])))->where('email', mb_strtolower($data['email']))->first();

        if (! $pqr) {
            return back()->withInput()->with('error', 'No encontramos una solicitud con ese radicado y ese correo.');
        }

        return view('shop.pqrs.status', ['pqr' => $pqr, 'justFiled' => false]);
    }
}
