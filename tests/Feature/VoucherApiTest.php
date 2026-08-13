<?php

use App\Jobs\SendVoucherSmsJob;
use App\Models\Campaign;
use App\Models\Redemption;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Sanctum::actingAs(User::factory()->create());
    Queue::fake();
});

test('competing issue calls for the final voucher allow only one claim', function (): void {
    $campaign = Campaign::factory()->create(['msisdn_cap' => 2]);
    $voucher = Voucher::factory()->for($campaign)->create([
        'issued_at' => null,
        'msisdn_hash' => null,
        'msisdn_encrypted' => null,
    ]);

    $first = $this->postJson('/api/v1/vouchers/issue', [
        'campaign_id' => $campaign->id,
        'msisdn' => '+27 82 123 4567',
    ]);
    $second = $this->postJson('/api/v1/vouchers/issue', [
        'campaign_id' => $campaign->id,
        'msisdn' => '+27 82 765 4321',
    ]);

    $first->assertCreated()->assertJsonPath('code', $voucher->code);
    $second->assertConflict();
    expect(Voucher::query()->whereNotNull('issued_at')->count())->toBe(1);
    Queue::assertPushed(SendVoucherSmsJob::class, 1);
});

test('the campaign cap is enforced for a normalised MSISDN', function (): void {
    $campaign = Campaign::factory()->create(['msisdn_cap' => 1]);
    Voucher::factory()->count(2)->for($campaign)->create([
        'issued_at' => null,
        'msisdn_hash' => null,
        'msisdn_encrypted' => null,
    ]);

    $this->postJson('/api/v1/vouchers/issue', [
        'campaign_id' => $campaign->id,
        'msisdn' => '+27 82 123 4567',
    ])->assertCreated();

    $this->postJson('/api/v1/vouchers/issue', [
        'campaign_id' => $campaign->id,
        'msisdn' => '27821234567',
    ])->assertUnprocessable();

    expect(Voucher::query()->whereNotNull('issued_at')->count())->toBe(1);
});

test('redeeming a voucher twice returns a conflict and creates one redemption', function (): void {
    $voucher = Voucher::factory()->create(['redeemed_at' => null]);

    $this->postJson("/api/v1/vouchers/{$voucher->code}/redeem")
        ->assertOk()
        ->assertJsonPath('code', $voucher->code);

    $this->postJson("/api/v1/vouchers/{$voucher->code}/redeem")
        ->assertConflict();

    expect(Redemption::query()->where('voucher_id', $voucher->id)->count())->toBe(1);
});

test('public validation does not require authentication', function (): void {
    $voucher = Voucher::factory()->create();
    $this->app['auth']->forgetGuards();

    $this->getJson("/api/v1/public/vouchers/{$voucher->code}/validate")
        ->assertOk()
        ->assertJsonPath('code', $voucher->code)
        ->assertJsonMissing(['msisdn_hash', 'msisdn_encrypted']);
});
