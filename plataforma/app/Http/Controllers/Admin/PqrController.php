<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PqrAnsweredMail;
use App\Models\Pqr;
use App\Support\Mailer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PqrController extends Controller
{
    public function index(Request $request)
    {
        $query = Pqr::query()->latest('id');
        if ($status = $request->query('estado')) {
            $status === 'abiertas' ? $query->whereIn('status', Pqr::OPEN) : $query->where('status', $status);
        }
        if ($type = $request->query('tipo')) {
            $query->where('type', $type);
        }
        if ($q = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('radicado', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('order_number', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"));
        }

        return view('admin.pqrs.index', [
            'pqrs' => $query->paginate(30)->withQueryString(),
            'openCount' => Pqr::whereIn('status', Pqr::OPEN)->count(),
            'overdueCount' => Pqr::whereIn('status', Pqr::OPEN)->where('due_at', '<', now())->count(),
        ]);
    }

    public function show(Pqr $pqr)
    {
        return view('admin.pqrs.show', ['pqr' => $pqr->load('order.sellerOrders.store', 'responder')]);
    }

    public function update(Request $request, Pqr $pqr)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Pqr::STATUSES))],
            'response' => [Rule::requiredIf(in_array($request->input('status'), ['respondida', 'cerrada'], true) && ! $pqr->response), 'nullable', 'string', 'max:5000'],
            'notify' => 'boolean',
        ], ['response.required' => 'Escribe la respuesta antes de marcarla como respondida.']);

        $newResponse = filled($data['response'] ?? null) && $data['response'] !== $pqr->response;
        $pqr->status = $data['status'];
        if ($newResponse) {
            $pqr->response = $data['response'];
            $pqr->responded_at = now();
            $pqr->responded_by = $request->user()->id;
        }
        $pqr->save();

        $pqr->order?->log('pqrs', "{$pqr->radicado}: {$pqr->statusLabel()}.", null, $request->user());

        if ($newResponse && $request->boolean('notify', true)) {
            Mailer::send($pqr->email, new PqrAnsweredMail($pqr));
        }

        return back()->with('success', $newResponse ? 'Respuesta guardada y enviada al cliente.' : 'Estado actualizado.');
    }
}
