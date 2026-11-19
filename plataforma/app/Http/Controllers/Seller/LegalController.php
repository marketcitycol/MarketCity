<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Mail\BankAccountChangeRequestedMail;
use App\Models\StoreBankAccount;
use App\Models\User;
use App\Support\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/** Datos legales y cuenta bancaria del vendedor. Cambiar la cuenta exige contraseña y pasa por revisión. */
class LegalController extends Controller
{
    public function show(Request $request)
    {
        $store = $request->user()->store;

        return view('seller.legal', [
            'store' => $store,
            'approved' => $store->approvedBankAccount(),
            'pending' => $store->pendingBankAccount(),
            'history' => $store->bankAccounts()->whereIn('status', ['rejected', 'replaced'])->take(5)->get(),
        ]);
    }

    public function updateLegal(Request $request)
    {
        $store = $request->user()->store;
        $data = $request->validate([
            'legal_name' => 'required|string|max:150',
            'document_type' => ['required', Rule::in(['CC', 'NIT', 'CE'])],
            'document_number' => ['required', 'string', 'max:30', 'regex:/^[0-9\-\.]+$/'],
            'rut' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], ['document_number.regex' => 'El documento solo lleva números, puntos o guion.']);

        $changes = ['legal_name' => $data['legal_name'], 'document_type' => $data['document_type'], 'document_number' => $data['document_number']];
        if ($request->hasFile('rut')) {
            $changes['rut_path'] = $request->file('rut')->store('rut/'.$store->id, 'local');
        }
        // Cambiar el titular legal quita la marca de verificada hasta que MarketCity vuelva a revisar.
        if ($store->isVerified() && ($store->document_number !== $data['document_number'] || $store->legal_name !== $data['legal_name'])) {
            $changes['verified_at'] = null;
        }
        $store->update($changes);

        return back()->with('success', 'Guardamos los datos legales de tu tienda.');
    }

    public function requestBankChange(Request $request)
    {
        $user = $request->user();
        $store = $user->store;
        $data = $request->validate([
            'bank' => 'required|string|max:60',
            'account_type' => ['required', Rule::in(StoreBankAccount::TYPES)],
            'account_number' => ['required', 'string', 'max:30', 'regex:/^[0-9 \-]{5,30}$/'],
            'holder_name' => 'required|string|max:150',
            'holder_document_type' => ['required', Rule::in(['CC', 'NIT', 'CE'])],
            'holder_document_number' => ['required', 'string', 'max:30', 'regex:/^[0-9\-\.]+$/'],
            'current_password' => 'required|string',
        ], [
            'account_number.regex' => 'Revisa el número de cuenta: solo números, espacios o guiones.',
            'holder_document_number.regex' => 'El documento solo lleva números, puntos o guion.',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withInput($request->except('current_password', 'account_number'))->with('error', 'La contraseña no es correcta. Por seguridad no guardamos el cambio.');
        }

        // Una sola solicitud en revisión: la nueva reemplaza a la anterior sin aprobar.
        $store->bankAccounts()->where('status', 'pending')->update(['status' => 'rejected', 'review_note' => 'Reemplazada por una solicitud más reciente del vendedor.', 'reviewed_at' => now()]);

        $account = $store->bankAccounts()->create([
            'bank' => $data['bank'],
            'account_type' => $data['account_type'],
            'account_number' => preg_replace('/\s+/', '', $data['account_number']),
            'holder_name' => $data['holder_name'],
            'holder_document_type' => $data['holder_document_type'],
            'holder_document_number' => $data['holder_document_number'],
            'status' => 'pending',
            'submitted_by' => $user->id,
            'submitted_ip' => $request->ip(),
        ]);

        // Aviso al vendedor (por si no fue él) y al equipo de MarketCity.
        Mailer::send($user->email, new BankAccountChangeRequestedMail($account, forSeller: true));
        foreach (User::where('role', User::ROLE_ADMIN)->pluck('email') as $admin) {
            Mailer::send($admin, new BankAccountChangeRequestedMail($account, forSeller: false));
        }

        return back()->with('success', 'Recibimos el cambio de cuenta. MarketCity lo revisa antes de usarla; mientras tanto tus liquidaciones quedan en espera.');
    }
}
