<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;

class PublicVoucherValidationController extends Controller
{
    public function __invoke(string $code): JsonResponse
    {
        $voucher = Voucher::query()->where('code', $code)->first();

        if ($voucher === null) {
            return response()->json(['message' => 'Voucher not found.'], 404);
        }

        return response()->json([
            'id' => $voucher->id,
            'campaign_id' => $voucher->campaign_id,
            'code' => $voucher->code,
            'issued_at' => $voucher->issued_at?->toISOString(),
            'redeemed_at' => $voucher->redeemed_at?->toISOString(),
            'is_redeemed' => $voucher->redeemed_at !== null,
        ]);
    }
}
