<?php

namespace App\Models;

use App\Enums\PaymentIntentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentIntent extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'merchant_id',
        'merchant_service_id',
        'payer_user_id',
        'amount_minor',
        'application_fee_minor',
        'currency',
        'status',
        'description',
        'idempotency',
        'batch_id',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'public_id' => 'string',
            'status' => PaymentIntentStatus::class,
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Merchant, $this>
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }

    /**
     * @return BelongsTo<LedgerBatch, $this>
     */
    public function ledgerBatch(): BelongsTo
    {
        return $this->belongsTo(LedgerBatch::class, 'batch_id');
    }

    /**
     * @return BelongsTo<MerchantService, $this>
     */
    public function merchantService(): BelongsTo
    {
        return $this->belongsTo(MerchantService::class);
    }
}
