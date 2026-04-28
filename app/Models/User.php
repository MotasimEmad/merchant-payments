<?php

namespace App\Models;

use App\Services\Ledger\LedgerService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return MorphMany<Wallet, $this>
     */
    public function wallets(): MorphMany
    {
        return $this->morphMany(Wallet::class, 'holder');
    }

    /**
     * @return HasMany<Merchant, $this>
     */
    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }

    public function isMerchant(): bool
    {
        return $this->role === 'merchant' || $this->merchants()->exists();
    }

    public function balanceMinor(string $currency = 'USD'): int
    {
        $wallet = Wallet::query()
            ->where('holder_type', self::class)
            ->where('holder_id', $this->id)
            ->where('currency', $currency)
            ->first();
        if (! $wallet) {
            return 0;
        }

        return app(LedgerService::class)->balanceMinor($wallet);
    }
}
