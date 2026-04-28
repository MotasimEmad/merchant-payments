<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerLine extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'batch_id',
        'wallet_id',
        'cents',
        'type',
        'link_type',
        'link_id',
        'extra',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'extra' => 'array',
        ];
    }

    /**
     * @return BelongsTo<LedgerBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(LedgerBatch::class, 'batch_id');
    }

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return MorphTo<Model|null, $this>
     */
    public function link(): MorphTo
    {
        return $this->morphTo();
    }
}
