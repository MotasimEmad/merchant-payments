<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use App\Services\Payments\WalletOperationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MerchantController extends Controller
{
    public function __construct(
        private readonly WalletOperationService $wallets,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }
        if ($user->merchants()->exists()) {
            return response()->json(['message' => 'You already have a merchant profile.'], 422);
        }
        $data = $request->validate([
            'business_name' => 'required|string|max:255',
        ]);
        $merchant = Merchant::query()->create([
            'public_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'business_name' => $data['business_name'],
            'status' => 'active',
        ]);
        if ($user->role === 'customer') {
            $user->update(['role' => 'merchant']);
        }
        $this->wallets->getOrCreateWalletForMerchant($merchant, 'USD');

        return response()->json([
            'merchant' => [
                'id' => $merchant->id,
                'public_id' => $merchant->public_id,
                'business_name' => $merchant->business_name,
            ],
        ], 201);
    }

    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }
        $m = $user->merchants()->first();
        if (! $m) {
            return response()->json(['merchant' => null]);
        }

        return response()->json([
            'merchant' => [
                'id' => $m->id,
                'public_id' => $m->public_id,
                'business_name' => $m->business_name,
            ],
        ]);
    }
}
