<?php

namespace App\Services;

use App\Models\Redemption;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Throwable;

class VoucherRedeemer
{
    /**
     * @return array{status: string, voucher?: Voucher}
     *
     * @throws Throwable
     */
    public function redeem(string $code): array
    {
        return DB::transaction(function () use ($code): array {
            $redeemedAt = now();

            $updated = Voucher::query()
                ->where('code', $code)
                ->whereNotNull('issued_at')
                ->whereNull('redeemed_at')
                ->update([
                    'redeemed_at' => $redeemedAt,
                    'updated_at' => $redeemedAt,
                ]);

            if ($updated === 0) {
                $voucher = Voucher::query()->where('code', $code)->first();

                if ($voucher === null) {
                    return ['status' => 'not_found'];
                }

                return ['status' => $voucher->issued_at === null ? 'not_issued' : 'already_redeemed'];
            }

            $voucher = Voucher::query()->where('code', $code)->firstOrFail();
            Redemption::query()->create([
                'voucher_id' => $voucher->id,
                'redeemed_at' => $redeemedAt,
            ]);

            return ['status' => 'redeemed', 'voucher' => $voucher];
        });
    }
}
