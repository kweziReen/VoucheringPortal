<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendVoucherSmsJob;
use App\Models\Voucher;
use App\Services\VoucherIssuer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class VoucherIssueController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(Request $request, VoucherIssuer $voucherIssuer): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => ['required', 'integer'],
            'msisdn' => ['required', 'string', 'min:3', 'max:32'],
        ]);

        $result = $voucherIssuer->issue($validated['campaign_id'], $validated['msisdn']);

        if ($result['status'] === 'issued') {
            SendVoucherSmsJob::dispatch($result['voucher']);
        }

        return match ($result['status']) {
            'issued' => response()->json($this->voucherPayload($result['voucher']), 201),
            'campaign_not_found' => response()->json(['message' => 'Campaign not found.'], 404),
            'invalid_msisdn' => response()->json(['message' => 'The MSISDN is invalid.'], 422),
            'cap_reached' => response()->json(['message' => 'MSISDN cap reached for this campaign.'], 422),
            default => response()->json(['message' => 'No vouchers are available for this campaign.'], 409),
        };
    }

    /** @return array<string, int|string|null> */
    private function voucherPayload(Voucher $voucher): array
    {
        return [
            'id' => $voucher->id,
            'campaign_id' => $voucher->campaign_id,
            'code' => $voucher->code,
            'issued_at' => $voucher->issued_at?->toISOString(),
            'redeemed_at' => $voucher->redeemed_at?->toISOString(),
        ];
    }
}
