<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MerchantServiceController extends Controller
{
    /**
     * Public catalog: active services for a merchant (by merchant public_id).
     */
    public function publicIndex(string $merchantPublicId): JsonResponse
    {
        $merchant = Merchant::query()
            ->where('public_id', $merchantPublicId)
            ->where('status', 'active')
            ->firstOrFail();

        $services = $merchant->services()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (MerchantService $s) => $this->publicServicePayload($s))
            ->values()
            ->all();

        return response()->json([
            'merchant' => [
                'public_id' => $merchant->public_id,
                'business_name' => $merchant->business_name,
            ],
            'services' => $services,
        ]);
    }

    public function indexMine(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $m = $user->merchants()->first();
        if (! $m) {
            return response()->json(['services' => []]);
        }
        $list = $m->services()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (MerchantService $s) => $this->manageServicePayload($s))
            ->values()
            ->all();

        return response()->json(['services' => $list]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $m = $user->merchants()->first();
        if (! $m) {
            return response()->json(['message' => 'You do not have a merchant profile.'], 422);
        }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price_minor' => 'required|integer|min:1',
            'currency' => 'required|string|size:3',
            'sort_order' => 'nullable|integer|min:0|max:32767',
        ]);
        $s = $m->services()->create([
            'public_id' => (string) Str::uuid(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price_minor' => (int) $data['price_minor'],
            'currency' => strtoupper($data['currency']),
            'status' => MerchantService::STATUS_ACTIVE,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return response()->json($this->manageServicePayload($s->fresh()), 201);
    }

    public function update(Request $request, string $servicePublicId): JsonResponse
    {
        $user = $this->user($request);
        $m = $user->merchants()->first();
        if (! $m) {
            return response()->json(['message' => 'You do not have a merchant profile.'], 422);
        }
        $s = $m->services()->where('public_id', $servicePublicId)->firstOrFail();
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price_minor' => 'sometimes|integer|min:1',
            'currency' => 'sometimes|string|size:3',
            'status' => 'sometimes|string|in:'.MerchantService::STATUS_ACTIVE.','.MerchantService::STATUS_INACTIVE,
            'sort_order' => 'nullable|integer|min:0|max:32767',
        ]);
        if (array_key_exists('name', $data)) {
            $s->name = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $s->description = $data['description'];
        }
        if (array_key_exists('price_minor', $data)) {
            $s->price_minor = (int) $data['price_minor'];
        }
        if (array_key_exists('currency', $data)) {
            $s->currency = strtoupper($data['currency']);
        }
        if (array_key_exists('status', $data)) {
            $s->status = $data['status'];
        }
        if (array_key_exists('sort_order', $data)) {
            $s->sort_order = (int) $data['sort_order'];
        }
        $s->save();

        return response()->json($this->manageServicePayload($s->fresh()));
    }

    public function destroy(Request $request, string $servicePublicId): JsonResponse
    {
        $user = $this->user($request);
        $m = $user->merchants()->first();
        if (! $m) {
            return response()->json(['message' => 'You do not have a merchant profile.'], 422);
        }
        $s = $m->services()->where('public_id', $servicePublicId)->firstOrFail();
        $s->update(['status' => MerchantService::STATUS_INACTIVE]);

        return response()->json($this->manageServicePayload($s->fresh()));
    }

    private function user(Request $request): User
    {
        $u = $request->user();
        if (! $u instanceof User) {
            abort(401);
        }

        return $u;
    }

    /**
     * @return array<string, mixed>
     */
    private function publicServicePayload(MerchantService $s): array
    {
        return [
            'public_id' => $s->public_id,
            'name' => $s->name,
            'description' => $s->description,
            'price_minor' => (int) $s->price_minor,
            'currency' => $s->currency,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function manageServicePayload(MerchantService $s): array
    {
        return [
            'public_id' => $s->public_id,
            'name' => $s->name,
            'description' => $s->description,
            'price_minor' => (int) $s->price_minor,
            'currency' => $s->currency,
            'status' => $s->status,
            'sort_order' => (int) $s->sort_order,
        ];
    }
}
