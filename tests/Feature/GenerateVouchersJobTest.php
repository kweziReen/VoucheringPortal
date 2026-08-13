<?php

use App\Jobs\GenerateVouchersJob;
use App\Models\Campaign;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('bulk generation inserts vouchers in batches without loading models into memory', function (): void {
    $campaign = Campaign::factory()->create();
    (new GenerateVouchersJob($campaign->id, 2_500))->handle();

    expect(Voucher::query()->where('campaign_id', $campaign->id)->count())->toBe(2_500)
        ->and(Voucher::query()->where('campaign_id', $campaign->id)->distinct()->count('code'))->toBe(2_500);
});
