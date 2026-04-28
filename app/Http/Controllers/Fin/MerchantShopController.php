<?php

namespace App\Http\Controllers\Fin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantService;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MerchantShopController extends Controller
{
    public function show(Request $request, string $merchantPublicId): InertiaResponse
    {
        $this->user($request);
        $merchant = Merchant::query()
            ->where('public_id', $merchantPublicId)
            ->where('status', 'active')
            ->firstOrFail();
        $services = $merchant->services()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (MerchantService $s) => [
                'public_id' => $s->public_id,
                'name' => $s->name,
                'description' => $s->description,
                'price_minor' => (int) $s->price_minor,
                'currency' => $s->currency,
            ])
            ->all();

        return Inertia::render('Fin/MerchantShop', [
            'merchant' => [
                'public_id' => $merchant->public_id,
                'business_name' => $merchant->business_name,
            ],
            'services' => $services,
        ]);
    }

    private function user(Request $request): User
    {
        $u = $request->user();
        if (! $u instanceof User) {
            abort(401);
        }

        return $u;
    }
}
