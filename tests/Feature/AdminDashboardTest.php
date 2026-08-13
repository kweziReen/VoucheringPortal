<?php

use App\Jobs\GenerateVouchersJob;
use App\Models\Campaign;
use App\Models\User;
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
