<?php

namespace App\Http\Controllers\Fin;

use App\Http\Controllers\Controller;
use App\Models\MerchantService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MerchantServicesController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $user = $this->user($request);
        $m = $user->merchants()->first();
        if (! $m) {
            return Inertia::render('Fin/MerchantServices', [
                'merchant' => null,
                'services' => [],
            ]);
        }
        $services = $m->services()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (MerchantService $s) => $this->serviceToArray($s))
            ->all();

        return Inertia::render('Fin/MerchantServices', [
            'merchant' => [
                'id' => $m->id,
                'public_id' => $m->public_id,
                'business_name' => $m->business_name,
            ],
            'services' => $services,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $m = $user->merchants()->firstOrFail();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0.01|max:999999.99',
            'currency' => 'required|string|size:3',
        ]);
        $m->services()->create([
            'public_id' => (string) Str::uuid(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price_minor' => (int) round((float) $data['price'] * 100),
            'currency' => strtoupper($data['currency']),
            'status' => MerchantService::STATUS_ACTIVE,
            'sort_order' => 0,
        ]);

        return redirect()->route('pay.merchant.services')
            ->with('flash', 'Service created.');
    }

    public function update(Request $request, string $servicePublicId): RedirectResponse
    {
        $user = $this->user($request);
        $m = $user->merchants()->firstOrFail();
        $s = $m->services()->where('public_id', $servicePublicId)->firstOrFail();
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'sometimes|numeric|min:0.01|max:999999.99',
            'currency' => 'sometimes|string|size:3',
            'status' => [
                'sometimes',
                'string',
                Rule::in([MerchantService::STATUS_ACTIVE, MerchantService::STATUS_INACTIVE]),
            ],
        ]);
        if (array_key_exists('name', $data)) {
            $s->name = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $s->description = $data['description'];
        }
        if (array_key_exists('price', $data)) {
            $s->price_minor = (int) round((float) $data['price'] * 100);
        }
        if (array_key_exists('currency', $data)) {
            $s->currency = strtoupper($data['currency']);
        }
        if (array_key_exists('status', $data)) {
            $s->status = $data['status'];
        }
        $s->save();

        return redirect()->route('pay.merchant.services')
            ->with('flash', 'Service updated.');
    }

    public function destroy(Request $request, string $servicePublicId): RedirectResponse
    {
        $user = $this->user($request);
        $m = $user->merchants()->firstOrFail();
        $s = $m->services()->where('public_id', $servicePublicId)->firstOrFail();
        $s->update(['status' => MerchantService::STATUS_INACTIVE]);

        return redirect()->route('pay.merchant.services')
            ->with('flash', 'Service removed from the catalog (inactive).');
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceToArray(MerchantService $s): array
    {
        return [
            'public_id' => $s->public_id,
            'name' => $s->name,
            'description' => $s->description,
            'price_minor' => (int) $s->price_minor,
            'currency' => $s->currency,
            'status' => $s->status,
        ];
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
