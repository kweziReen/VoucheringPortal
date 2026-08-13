<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Voucher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateVouchersJob implements ShouldQueue
{
    use Queueable;

    private const CHUNK_SIZE = 1_000;

    public int $timeout = 600;

    public int $tries = 3;

    public function __construct(public int $campaignId, public int $quantity) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(): void
    {
        Campaign::query()->findOrFail($this->campaignId);
        $timestamp = now()->format('Y-m-d H:i:s');
        $rows = [];

        for ($index = 0; $index < $this->quantity; $index++) {
            $rows[] = [
                'campaign_id' => $this->campaignId,
                'code' => 'VCH-'.strtoupper(bin2hex(random_bytes(12))),
                'msisdn_hash' => null,
                'msisdn_encrypted' => null,
                'issued_at' => null,
                'redeemed_at' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            if (count($rows) === self::CHUNK_SIZE) {
                Voucher::query()->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            Voucher::query()->insert($rows);
        }
    }
}
