<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Throwable;

class VoucherIssuer
{
    /**
     * @return array{status: string, voucher?: Voucher}
     *
     * @throws Throwable
     */
    public function issue(int $campaignId, string $msisdn): array
    {
        $normalisedMsisdn = $this->normaliseMsisdn($msisdn);

        if ($normalisedMsisdn === '') {
            return ['status' => 'invalid_msisdn'];
        }

        return DB::transaction(function () use ($campaignId, $normalisedMsisdn): array {
            $campaign = Campaign::query()->lockForUpdate()->find($campaignId);

            if ($campaign === null) {
                return ['status' => 'campaign_not_found'];
            }

            $msisdnHash = hash('sha256', $normalisedMsisdn);

            if (Voucher::query()
                ->where('campaign_id', $campaign->id)
                ->where('msisdn_hash', $msisdnHash)
                ->whereNotNull('issued_at')
                ->count() >= $campaign->msisdn_cap) {
                return ['status' => 'cap_reached'];
            }

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
                    'msisdn_encrypted' => $normalisedMsisdn,
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
    }

    private function normaliseMsisdn(string $msisdn): string
    {
        return preg_replace('/\D+/', '', $msisdn) ?? '';
    }
}
