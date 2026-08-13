<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;

class VoucherIssued implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly string $voucherCode,
        public readonly int $campaignId,
        public readonly Carbon $issuedAt,
        public readonly string $smsStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.vouchers')];
    }

    public function broadcastAs(): string
    {
        return 'voucher.issued';
    }

    /** @return array{voucher_code: string, campaign_id: int, issued_at: string, sms_status: string} */
    public function broadcastWith(): array
    {
        return [
            'voucher_code' => $this->voucherCode,
            'campaign_id' => $this->campaignId,
            'issued_at' => $this->issuedAt->toISOString(),
            'sms_status' => $this->smsStatus,
        ];
    }
}
