<?php

use App\Jobs\GenerateVouchersJob;
use App\Jobs\SendVoucherSmsJob;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    Role::findOrCreate('admin');
    Role::findOrCreate('viewer');
});

test('a viewer can view the read-only dashboard but cannot generate vouchers', function (): void {
    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');
    $campaign = Campaign::factory()->create();

    $this->actingAs($viewer)->get('/admin')->assertOk()->assertSee('Read only');
    $this->post("/admin/campaigns/{$campaign->id}/vouchers", ['quantity' => 100])->assertForbidden();
});

test('an admin can queue a bulk voucher generation job', function (): void {
    Queue::fake();
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $campaign = Campaign::factory()->create();

    $this->actingAs($admin)->post("/admin/campaigns/{$campaign->id}/vouchers", ['quantity' => 100])
        ->assertRedirect();

    Queue::assertPushed(GenerateVouchersJob::class, fn (GenerateVouchersJob $job) => $job->campaignId === $campaign->id
        && $job->quantity === 100);
});

test('an admin can create a campaign while a viewer cannot', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->post('/admin/campaigns', ['name' => 'Summer vouchers', 'msisdn_cap' => 3])
        ->assertRedirect();
    $this->assertDatabaseHas('campaigns', ['name' => 'Summer vouchers', 'msisdn_cap' => 3]);

    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');
    $this->actingAs($viewer)->post('/admin/campaigns', ['name' => 'Blocked campaign', 'msisdn_cap' => 1])
        ->assertForbidden();
});

test('an admin can issue a voucher from the dashboard and queue its SMS', function (): void {
    Queue::fake();
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $campaign = Campaign::factory()->create();
    $voucher = Voucher::factory()->for($campaign)->create([
        'issued_at' => null,
        'msisdn_hash' => null,
        'msisdn_encrypted' => null,
    ]);

    $this->actingAs($admin)->post("/admin/campaigns/{$campaign->id}/issue", ['msisdn' => '+27821234567'])
        ->assertRedirect()
        ->assertSessionHas('status', "Voucher {$voucher->code} was issued and queued for SMS delivery.");

    $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'msisdn_hash' => hash('sha256', '27821234567')]);
    Queue::assertPushed(SendVoucherSmsJob::class, 1);
});
