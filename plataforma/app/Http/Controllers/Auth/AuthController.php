<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required']);
        // El correo se compara sin mayúsculas (SQLite distingue mayúsculas).
        $credentials['email'] = User::findByEmail($credentials['email'])?->email ?? $credentials['email'];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->with('error', 'El correo o la contraseña no coinciden.');
        }

        $request->session()->regenerate();
        $user = $request->user();

        return redirect()->intended(match ($user->role) {
            'admin' => route('admin.dashboard'),
            'seller' => route('seller.dashboard'),
            default => route('account'),
        });
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:120|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'accepted',
        ], ['terms.accepted' => 'Debes aceptar la política de tratamiento de datos.']);

        $user = User::create($data + ['role' => 'customer']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account')->with('success', 'Tu cuenta quedó creada.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function account(Request $request)
    {
        $user = $request->user();

        return view('auth.account', [
            'user' => $user,
            // Los pedidos como invitado con el mismo correo solo se muestran si el correo está verificado;
            // si no, cualquiera que se registre con un correo ajeno vería esos pedidos (MC-NFR-012).
            'orders' => Order::where('user_id', $user->id)
                ->when($user->email_verified_at, fn ($q) => $q->orWhere(fn ($g) => $g->whereNull('user_id')->where('email', $user->email)))
                ->latest('id')->with('sellerOrders')->get(),
        ]);
    }
}
