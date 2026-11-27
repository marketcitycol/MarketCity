<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Store;
use App\Support\ColombiaCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Backoffice → Cupones (en WooCommerce: Marketing → Cupones). */
class CouponController extends Controller
{
    public function index()
    {
        return view('admin.coupons.index', [
            'coupons' => Coupon::with('store')->withCount(['orders as uses' => fn ($q) => $q->whereIn('payment_status', ['pending', 'paid'])])
                ->withSum(['orders as discounted' => fn ($q) => $q->whereIn('payment_status', ['pending', 'paid'])], 'discount_total')
                ->latest('id')->paginate(30),
        ]);
    }

    public function create()
    {
        return view('admin.coupons.form', ['coupon' => new Coupon(['type' => 'percent', 'active' => true]), 'stores' => $this->stores()]);
    }

    public function store(Request $request)
    {
        $coupon = Coupon::create($this->validated($request));

        return redirect()->route('admin.coupons.edit', $coupon)->with('success', "Cupón {$coupon->code} creado.");
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.form', ['coupon' => $coupon, 'stores' => $this->stores()]);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon->update($this->validated($request, $coupon));

        return back()->with('success', 'Cupón guardado.');
    }

    private function stores()
    {
        return Store::orderBy('name')->get(['id', 'name']);
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['code' => Coupon::normalize((string) $request->input('code'))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Z0-9\-_]+$/', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'description' => 'nullable|string|max:200',
            'type' => ['required', Rule::in(array_keys(Coupon::TYPES))],
            'amount' => ['required_unless:type,free_shipping', 'nullable', 'integer', 'min:1', $request->input('type') === 'percent' ? 'max:100' : 'max:10000000'],
            'max_discount' => 'nullable|integer|min:1',
            'min_subtotal' => 'nullable|integer|min:0',
            'store_id' => 'nullable|integer|exists:stores,id',
            'exclude_sale_items' => 'nullable|boolean',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date_format:Y-m-d\TH:i',
            'ends_at' => 'nullable|date_format:Y-m-d\TH:i|after:starts_at',
            'active' => 'nullable|boolean',
        ], ['code.regex' => 'El código solo puede tener letras, números, guiones y guion bajo.', 'amount.max' => 'Revisa el valor: un porcentaje va de 1 a 100.']);
        foreach (['starts_at', 'ends_at'] as $k) {
            $data[$k] = ! empty($data[$k]) ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data[$k], ColombiaCalendar::TZ)->utc() : null;
        }
        $data['amount'] = $data['type'] === 'free_shipping' ? 0 : (int) $data['amount'];
        $data['max_discount'] = $data['type'] === 'percent' ? ($data['max_discount'] ?? null) : null;
        $data['min_subtotal'] = (int) ($data['min_subtotal'] ?? 0);
        $data['exclude_sale_items'] = (bool) ($data['exclude_sale_items'] ?? false);
        $data['active'] = (bool) ($data['active'] ?? false);

        return $data;
    }
}
