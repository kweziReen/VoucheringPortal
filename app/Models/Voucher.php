<?php

namespace App\Models;

use Database\Factories\VoucherFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'campaign_id',
        'code',
        'msisdn_hash',
        'msisdn_encrypted',
        'issued_at',
        'redeemed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'redeemed_at' => 'datetime',
            'msisdn_encrypted' => 'encrypted',
        ];
    }

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return HasOne<Redemption, $this> */
    public function redemption(): HasOne
    {
        return $this->hasOne(Redemption::class);
    }
}
