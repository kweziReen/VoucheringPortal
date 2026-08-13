<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VoucherRedeemer;
use Illuminate\Http\JsonResponse;
use Throwable;

class VoucherRedemptionController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(string $code, VoucherRedeemer $voucherRedeemer): JsonResponse
    {
        $result = $voucherRedeemer->redeem($code);

        return match ($result['status']) {
            'redeemed' => response()->json([
                'id' => $result['voucher']->id,
                'code' => $result['voucher']->code,
                'redeemed_at' => $result['voucher']->redeemed_at?->toISOString(),
            ]),
            'already_redeemed' => response()->json(['message' => 'Voucher has already been redeemed.'], 409),
            'not_issued' => response()->json(['message' => 'Voucher has not been issued.'], 409),
            default => response()->json(['message' => 'Voucher not found.'], 404),
        };
    }
}
