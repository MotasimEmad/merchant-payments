<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerBatch extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ref',
        'idempotency',
        'name',
        'channel',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ref' => 'string',
        ];
    }

    /**
     * @return HasMany<LedgerLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(LedgerLine::class, 'batch_id');
    }
}
