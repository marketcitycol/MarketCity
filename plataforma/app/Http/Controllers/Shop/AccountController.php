<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Address;
use App\Models\Order;
use App\Models\Pqr;
use App\Models\Product;
use App\Models\User;
use App\Services\Marketing\MessagingPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * «Mi cuenta» (traslado de los snippets #48, #49 y #79): inicio, pedidos, direcciones, mis datos,
 * seguridad, favoritos, notificaciones y consultas (PQRS).
 */
class AccountController extends Controller
{
    /**
     * Pedidos de la cuenta. Los hechos como invitado con el mismo correo solo se suman si el correo
     * está verificado; si no, cualquiera que se registre con un correo ajeno los vería (MC-NFR-012).
     */
    private function ordersOf(User $user): Builder
    {
        return Order::where('user_id', $user->id)
            ->when($user->email_verified_at, fn ($q) => $q->orWhere(fn ($g) => $g->whereNull('user_id')->where('email', $user->email)));
    }

    public function index(Request $request)
    {
        $user = $request->user();

        return view('account.home', [
            'user' => $user,
            'orders' => $this->ordersOf($user)->latest('id')->with('sellerOrders')->take(5)->get(),
            'counts' => [
                'pedidos' => $this->ordersOf($user)->count(),
                'direcciones' => $user->addresses()->count(),
                'favoritos' => $user->favorites()->count(),
            ],
        ]);
    }

    public function orders(Request $request)
    {
        return view('account.orders', [
            'user' => $request->user(),
            'orders' => $this->ordersOf($request->user())->latest('id')->with('sellerOrders')->paginate(15),
        ]);
    }

    // --- Direcciones ---

    public function addresses(Request $request)
    {
        return view('account.addresses', ['user' => $request->user(), 'addresses' => $request->user()->addresses()->get()]);
    }

    private function addressRules(): array
    {
        return [
            'label' => 'nullable|string|max:40',
            'recipient' => 'nullable|string|max:120',
            'phone' => 'nullable|string|regex:/^[0-9 +]{7,15}$/',
            'department' => ['required', Rule::in(array_keys(config('colombia.departments')))],
            'city' => 'required|string|max:80',
            'address' => 'required|string|max:150',
            'address_2' => 'nullable|string|max:150',
        ];
    }

    public function storeAddress(Request $request)
    {
        $user = $request->user();
        if ($user->addresses()->count() >= 10) {
            return back()->with('error', 'Puedes guardar hasta 10 direcciones. Borra una para agregar otra.');
        }
        $data = $request->validate($this->addressRules(), ['phone.regex' => 'Revisa el número de celular.']);
        $address = $user->addresses()->create($data + ['is_default' => ! $user->addresses()->exists()]);
        if ($request->boolean('is_default')) {
            $this->makeDefault($user, $address);
        }

        return redirect()->route('account.addresses')->with('success', 'Guardamos la dirección.');
    }

    public function updateAddress(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $address->update($request->validate($this->addressRules(), ['phone.regex' => 'Revisa el número de celular.']));
        if ($request->boolean('is_default')) {
            $this->makeDefault($request->user(), $address);
        }

        return redirect()->route('account.addresses')->with('success', 'Actualizamos la dirección.');
    }

    public function defaultAddress(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $this->makeDefault($request->user(), $address);

        return back()->with('success', 'Esa es ahora tu dirección principal.');
    }

    public function destroyAddress(Request $request, Address $address)
    {
        $user = $request->user();
        abort_unless($address->user_id === $user->id, 404);
        $wasDefault = $address->is_default;
        $address->delete();
        if ($wasDefault && ($next = $user->addresses()->first())) {
            $this->makeDefault($user, $next);
        }

        return back()->with('success', 'Borramos la dirección.');
    }

    private function makeDefault(User $user, Address $address): void
    {
        DB::transaction(function () use ($user, $address) {
            Address::where('user_id', $user->id)->where('id', '!=', $address->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });
    }

    /** Primera compra con sesión: la dirección del pedido queda guardada como principal. */
    public static function rememberAddress(?User $user, Order $order): void
    {
        if (! $user || $user->role !== User::ROLE_CUSTOMER || $user->addresses()->exists()) {
            return;
        }
        $user->addresses()->create([
            'label' => 'Principal', 'recipient' => $order->customerName(), 'phone' => $order->phone,
            'department' => $order->department, 'city' => $order->city, 'address' => $order->address,
            'address_2' => $order->address_2, 'is_default' => true,
        ]);
        $user->forceFill(array_filter([
            'phone' => $user->phone ?: $order->phone,
            'document_type' => $user->document_number ? null : $order->document_type,
            'document_number' => $user->document_number ?: $order->document_number,
        ]))->save();
    }

    // --- Mis datos ---

    public function details(Request $request)
    {
        return view('account.details', ['user' => $request->user()]);
    }

    public function updateDetails(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => 'nullable|string|regex:/^[0-9 +]{7,15}$/',
            'document_type' => ['nullable', Rule::in(array_keys(config('colombia.document_types')))],
            'document_number' => 'nullable|string|regex:/^[0-9A-Za-z\-]{5,20}$/',
        ], ['phone.regex' => 'Revisa el número de celular.', 'document_number.regex' => 'Revisa el número de documento.']);
        $request->user()->update($data);

        return back()->with('success', 'Guardamos tus datos.');
    }

    // --- Seguridad ---

    public function security(Request $request)
    {
        return view('account.security', ['user' => $request->user()]);
    }

    /**
     * Eliminar la cuenta (derecho de supresión, Ley 1581): se borran la cuenta, las direcciones y los
     * favoritos, se retiran los permisos de mensajes y se detienen los recordatorios. Los pedidos se
     * conservan sin la cuenta porque la ley obliga a guardar las facturas y atender garantías.
     */
    public function destroy(Request $request, MessagingPermissions $permissions)
    {
        $user = $request->user();
        if ($user->role !== User::ROLE_CUSTOMER) {
            return back()->with('error', 'Las cuentas de vendedores y del equipo se cierran escribiendo a soporte.');
        }
        $request->validate(['current_password' => 'required|current_password', 'confirm' => 'accepted'], [
            'confirm.accepted' => 'Marca la casilla para confirmar que quieres eliminar tu cuenta.',
        ]);

        DB::transaction(function () use ($user, $permissions, $request) {
            $permissions->record($user->email, $user->phone, ['email' => false, 'whatsapp' => false], ['source' => 'cuenta-eliminada', 'ip' => $request->ip()]);
            AbandonedCart::where('status', 'open')->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('email', mb_strtolower($user->email)))
                ->update(['status' => 'stopped', 'stop_reason' => 'cuenta-eliminada', 'next_reminder_at' => null]);
            $user->delete();
        });
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Eliminamos tu cuenta. Tus pedidos se conservan solo por obligación legal.');
    }

    // --- Favoritos ---

    public function favorites(Request $request)
    {
        return view('account.favorites', [
            'user' => $request->user(),
            'favorites' => $request->user()->favorites()->with('product.store')->get()->filter(fn ($f) => $f->product),
        ]);
    }

    public function toggleFavorite(Request $request, Product $product)
    {
        $user = $request->user();
        $existing = $user->favorites()->where('product_id', $product->id)->first();
        if ($existing) {
            $existing->delete();
        } else {
            if ($user->favorites()->count() >= 200) {
                return back()->with('error', 'Llegaste al máximo de 200 favoritos.');
            }
            $user->favorites()->create(['product_id' => $product->id]);
        }
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'favorite' => ! $existing]);
        }

        return back()->with('success', $existing ? 'Lo quitamos de tus favoritos.' : 'Lo guardamos en tus favoritos.');
    }

    // --- Notificaciones ---

    public function notifications(Request $request, MessagingPermissions $permissions)
    {
        $user = $request->user();
        $phone = MessagingPermissions::normalizePhone($user->phone);

        return view('account.notifications', [
            'user' => $user,
            'phone' => $phone,
            'email' => $permissions->allows(MessagingPermissions::normalizeEmail($user->email), 'email', 'ofertas'),
            'whatsapp' => $permissions->allows($phone, 'whatsapp', 'ofertas'),
        ]);
    }

    public function updateNotifications(Request $request, MessagingPermissions $permissions)
    {
        $user = $request->user();
        $channels = ['email' => $request->boolean('email'), 'whatsapp' => $request->boolean('whatsapp')];
        if ($channels['whatsapp'] && ! MessagingPermissions::normalizePhone($user->phone)) {
            return back()->with('error', 'Agrega un celular válido en «Mis datos» para recibir mensajes por WhatsApp.');
        }
        $permissions->record($user->email, $user->phone, $channels, [
            'source' => 'cuenta', 'ip' => $request->ip(), 'user_agent' => $request->userAgent(), 'user_id' => $user->id,
        ]);

        return back()->with('success', 'Guardamos tus preferencias de mensajes.');
    }

    // --- Consultas (PQRS) ---

    public function inquiries(Request $request)
    {
        $user = $request->user();

        return view('account.inquiries', [
            'user' => $user,
            'pqrs' => Pqr::where('user_id', $user->id)
                ->when($user->email_verified_at, fn ($q) => $q->orWhere('email', $user->email))
                ->latest('id')->get(),
        ]);
    }
}
