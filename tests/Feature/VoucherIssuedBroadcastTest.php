<?php

use App\Contracts\SmsService;
use App\Events\VoucherIssued;
use App\Jobs\SendVoucherSmsJob;
use App\Models\Voucher;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('a successful SMS sends a PII-safe voucher issued broadcast', function (): void {
    Event::fake();
    Http::fake([config('sms.fake.endpoint') => Http::response([], 202)]);
    $voucher = Voucher::factory()->create([
        'code' => 'VOUCHER-123',
        'msisdn_encrypted' => '+27821234567',
        'issued_at' => now(),
    ]);

    (new SendVoucherSmsJob($voucher))->handle(app(SmsService::class));

    Event::assertDispatched(VoucherIssued::class, function (VoucherIssued $event) use ($voucher): bool {
        $payload = $event->broadcastWith();

        expect($event->broadcastOn())->toEqual([new PrivateChannel('admin.vouchers')])
            ->and(array_keys($payload))->toBe(['voucher_code', 'campaign_id', 'issued_at', 'sms_status'])
            ->and($payload)->toMatchArray([
                'voucher_code' => $voucher->code,
                'campaign_id' => $voucher->campaign_id,
                'issued_at' => $voucher->issued_at->toISOString(),
                'sms_status' => 'sent',
            ])
            ->and($payload)->not->toHaveKey('msisdn')
            ->and($payload)->not->toHaveKey('msisdn_hash')
            ->and($payload)->not->toHaveKey('msisdn_encrypted')
            ->and(json_encode($payload))->not->toContain('27821234567');

        return true;
    });
});
