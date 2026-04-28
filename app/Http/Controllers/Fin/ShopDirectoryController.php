<?php

namespace App\Http\Controllers\Fin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ShopDirectoryController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $this->user($request);
        $merchants = Merchant::query()
            ->where('status', 'active')
            ->whereHas('services', function ($q): void {
                $q->where('status', 'active');
            })
            ->orderBy('business_name')
            ->get()
            ->map(fn (Merchant $m) => [
                'public_id' => $m->public_id,
                'business_name' => $m->business_name,
            ])
            ->all();

        return Inertia::render('Fin/ShopDirectory', [
            'merchants' => $merchants,
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
