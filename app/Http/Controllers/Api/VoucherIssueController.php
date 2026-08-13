<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendVoucherSmsJob;
use App\Models\Campaign;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class VoucherIssueController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => ['required', 'integer'],
            'msisdn' => ['required', 'string', 'min:3', 'max:32'],
        ]);

        $msisdn = $this->normaliseMsisdn($validated['msisdn']);

        if ($msisdn === '') {
            return response()->json(['message' => 'The MSISDN is invalid.'], 422);
        }

        $result = DB::transaction(function () use ($validated, $msisdn): array {
            // This lock serializes cap checks for a campaign. The voucher itself is
            // still claimed by the conditional UPDATE below, never by model save().
            $campaign = Campaign::query()->lockForUpdate()->find($validated['campaign_id']);

            if ($campaign === null) {
                return ['status' => 'campaign_not_found'];
            }

            $msisdnHash = hash('sha256', $msisdn);

            if (Voucher::query()
                ->where('campaign_id', $campaign->id)
                ->where('msisdn_hash', $msisdnHash)
                ->whereNotNull('issued_at')
                ->count() >= $campaign->msisdn_cap) {
                return ['status' => 'cap_reached'];
            }

            // Reading the immutable code identifies the response payload. The claim
            // remains race-safe because the subsequent UPDATE requires issued_at to
            // still be NULL and affects at most one row.
            $code = Voucher::query()
                ->where('campaign_id', $campaign->id)
                ->whereNull('issued_at')
                ->orderBy('id')
                ->value('code');

            if ($code === null) {
                return ['status' => 'unavailable'];
            }

            $claimed = Voucher::query()
                ->where('campaign_id', $campaign->id)
                ->where('code', $code)
                ->whereNull('issued_at')
                ->limit(1)
                ->update([
                    'msisdn_hash' => $msisdnHash,
                    'msisdn_encrypted' => $msisdn,
                    'issued_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($claimed !== 1) {
                return ['status' => 'unavailable'];
            }

            return [
                'status' => 'issued',
                'voucher' => Voucher::query()->where('code', $code)->firstOrFail(),
            ];
        });

        if ($result['status'] === 'issued') {
            SendVoucherSmsJob::dispatch($result['voucher']);
        }

        return match ($result['status']) {
            'issued' => response()->json($this->voucherPayload($result['voucher']), 201),
            'campaign_not_found' => response()->json(['message' => 'Campaign not found.'], 404),
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

    private function normaliseMsisdn(string $msisdn): string
    {
        return preg_replace('/\D+/', '', $msisdn) ?? '';
    }
}
