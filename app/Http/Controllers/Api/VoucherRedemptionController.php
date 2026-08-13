<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Redemption;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class VoucherRedemptionController extends Controller
{
    public function __invoke(string $code): JsonResponse
    {
        $result = DB::transaction(function () use ($code): array {
            $redeemedAt = now();

            $updated = Voucher::query()
                ->where('code', $code)
                ->whereNull('redeemed_at')
                ->update([
                    'redeemed_at' => $redeemedAt,
                    'updated_at' => $redeemedAt,
                ]);

            if ($updated === 0) {
                return [
                    'status' => Voucher::query()->where('code', $code)->exists() ? 'already_redeemed' : 'not_found',
                ];
            }

            $voucher = Voucher::query()->where('code', $code)->firstOrFail();

            Redemption::query()->create([
                'voucher_id' => $voucher->id,
                'redeemed_at' => $redeemedAt,
            ]);

            return ['status' => 'redeemed', 'voucher' => $voucher];
        });

        return match ($result['status']) {
            'redeemed' => response()->json([
                'id' => $result['voucher']->id,
                'code' => $result['voucher']->code,
                'redeemed_at' => $result['voucher']->redeemed_at?->toISOString(),
            ]),
            'already_redeemed' => response()->json(['message' => 'Voucher has already been redeemed.'], 409),
            default => response()->json(['message' => 'Voucher not found.'], 404),
        };
    }
}
