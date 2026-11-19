<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SellerSignupController extends Controller
{
    public function show()
    {
        return view('shop.sell');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:120|unique:users,email',
            'phone' => 'required|string|regex:/^[0-9 +]{7,15}$/',
            'password' => 'required|string|min:8|confirmed',
            'store_name' => 'required|string|max:80',
            'document_type' => ['required', Rule::in(['CC', 'CE', 'NIT'])],
            'document_number' => 'required|string|regex:/^[0-9\-]{5,20}$/',
            'department' => ['required', Rule::in(array_keys(config('colombia.departments')))],
            'city' => 'required|string|max:80',
            'terms' => 'accepted',
        ], ['terms.accepted' => 'Debes aceptar los términos para vendedores.']);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'],
                'phone' => $data['phone'], 'role' => 'seller',
                'document_type' => $data['document_type'], 'document_number' => $data['document_number'],
            ]);
            $slug = Str::slug($data['store_name']);
            if (Store::where('slug', $slug)->exists()) {
                $slug .= '-'.Str::lower(Str::random(4));
            }
            Store::create([
                'user_id' => $user->id, 'name' => $data['store_name'], 'slug' => $slug,
                'department' => $data['department'], 'city' => $data['city'], 'phone' => $data['phone'],
                'document_type' => $data['document_type'], 'document_number' => $data['document_number'],
                'status' => 'pending',
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('seller.dashboard')->with('success', 'Recibimos tu solicitud. Ya puedes cargar productos; se publican cuando aprobemos tu tienda.');
    }
}
