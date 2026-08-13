<?php

use App\Models\Campaign;
use App\Models\Redemption;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

test('voucher factories persist PII using the encrypted cast', function (): void {
    $msisdn = '+27821234567';

    $voucher = Voucher::factory()->create([
        'msisdn_hash' => hash('sha256', $msisdn),
        'msisdn_encrypted' => $msisdn,
    ]);

    expect($voucher->campaign)->toBeInstanceOf(Campaign::class)
        ->and($voucher->issued_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($voucher->msisdn_encrypted)->toBe($msisdn)
        ->and($voucher->getRawOriginal('msisdn_encrypted'))->not->toBe($msisdn)
        ->and(Crypt::decryptString($voucher->getRawOriginal('msisdn_encrypted')))->toBe($msisdn);
});

test('redemption factories create a voucher relationship', function (): void {
    $redemption = Redemption::factory()->create();

    expect($redemption->voucher)->toBeInstanceOf(Voucher::class)
        ->and($redemption->redeemed_at)->toBeInstanceOf(DateTimeInterface::class);
});
