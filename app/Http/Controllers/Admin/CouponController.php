<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Coupon;
use App\Enums\CouponType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(Request $request): Response
    {
        $coupons = Coupon::withCount('orders')
            ->when($request->search, fn($q) => $q->where('code', 'like', "%{$request->search}%")
                ->orWhere('name', 'like', "%{$request->search}%")
            )
            ->when($request->active !== null, fn($q) => $q->where('is_active', (bool) $request->active))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => $coupons->through(fn($c) => [
                'id'              => $c->id,
                'code'            => $c->code,
                'name'            => $c->name,
                'type'            => $c->type->value,
                'type_label'      => $c->type->label(),
                'value'           => $c->value,
                'min_order_amount'=> $c->min_order_amount,
                'max_uses'        => $c->max_uses,
                'uses_count'      => $c->uses_count,
                'is_active'       => $c->is_active,
                'is_valid'        => $c->is_valid,
                'expires_at'      => $c->expires_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'orders_count'    => $c->orders_count,
            ]),
            'filters' => $request->only(['search', 'active']),
            'types'   => collect(CouponType::cases())->map(fn($t) => [
                'value' => $t->value, 'label' => $t->label(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Coupons/Create', [
            'types'       => collect(CouponType::cases())->map(fn($t) => ['value' => $t->value, 'label' => $t->label()]),
            'suggestions' => [Str::upper(Str::random(6)), Str::upper(Str::random(8))],
        ]);
    }

    public function store(CouponRequest $request): RedirectResponse
    {
        $coupon = Coupon::create($request->validated());

        return redirect()->route('admin.coupons.index')
                         ->with('success', "Coupon \"{$coupon->code}\" créé.");
    }

    public function show(Coupon $coupon): Response
    {
        $coupon->loadCount('orders');

        return Inertia::render('Admin/Coupons/Show', [
            'coupon' => [
                ...$coupon->toArray(),
                'type_label'      => $coupon->type->label(),
                'is_valid'        => $coupon->is_valid,
                'starts_at'       => $coupon->starts_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'expires_at'      => $coupon->expires_at?->locale('fr')->isoFormat('D MMM YYYY'),
            ],
        ]);
    }

    public function edit(Coupon $coupon): Response
    {
        return Inertia::render('Admin/Coupons/Edit', [
            'coupon' => $coupon,
            'types'  => collect(CouponType::cases())->map(fn($t) => ['value' => $t->value, 'label' => $t->label()]),
        ]);
    }

    public function update(CouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($request->validated());

        return redirect()->route('admin.coupons.index')
                         ->with('success', "Coupon \"{$coupon->code}\" mis à jour.");
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $code = $coupon->code;
        $coupon->delete();

        return redirect()->route('admin.coupons.index')
                         ->with('success', "Coupon {$code} supprimé.");
    }

    public function toggle(Coupon $coupon): RedirectResponse
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);
        $state = $coupon->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Coupon {$coupon->code} {$state}.");
    }
}