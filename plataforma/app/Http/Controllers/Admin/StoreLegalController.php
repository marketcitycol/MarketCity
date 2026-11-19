<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreBankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Revisión de cuentas bancarias, verificación de tiendas y RUT. */
class StoreLegalController extends Controller
{
    public function approve(Request $request, Store $store, StoreBankAccount $account)
    {
        abort_unless($account->store_id === $store->id && $account->status === 'pending', 404);
        DB::transaction(function () use ($request, $store, $account) {
            $store->bankAccounts()->where('status', 'approved')->update(['status' => 'replaced']);
            $account->update(['status' => 'approved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $request->input('note')]);
        });

        return back()->with('success', 'Cuenta aprobada. Las liquidaciones de la tienda se pagan a esta cuenta.');
    }

    public function reject(Request $request, Store $store, StoreBankAccount $account)
    {
        abort_unless($account->store_id === $store->id && $account->status === 'pending', 404);
        $data = $request->validate(['note' => 'required|string|max:300']);
        $account->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['note']]);

        return back()->with('success', 'Solicitud rechazada. La tienda sigue con su cuenta anterior, si tenía una aprobada.');
    }

    public function verify(Request $request, Store $store)
    {
        $store->update(['verified_at' => $request->boolean('verified') ? now() : null]);

        return back()->with('success', $store->isVerified() ? 'Tienda marcada como verificada.' : 'Se quitó la verificación.');
    }

    public function rut(Store $store)
    {
        abort_unless($store->rut_path && Storage::disk('local')->exists($store->rut_path), 404);

        return Storage::disk('local')->download($store->rut_path, 'RUT-'.$store->slug.'.'.pathinfo($store->rut_path, PATHINFO_EXTENSION));
    }
}
