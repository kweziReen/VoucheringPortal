<?php

use App\Contracts\SmsService;
use App\Jobs\SendVoucherSmsJob;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('sms.fake.endpoint', 'https://sms.fake.test/api/messages');
    config()->set('sms.fake.timeout', 1);
    config()->set('sms.fake.connect_timeout', 1);
});

test('a fake SMS timeout fails the attempt so the worker retries it', function (): void {
    Http::fake(fn () => throw new ConnectionException('SMS request timed out.'));
    $voucher = Voucher::factory()->create(['msisdn_encrypted' => '+27821234567']);
    $job = new SendVoucherSmsJob($voucher);

    expect(fn () => $job->handle(app(SmsService::class)))->toThrow(ConnectionException::class);
    expect($job->tries)->toBe(4)
        ->and($job->timeout)->toBe(15)
        ->and($job->failOnTimeout)->toBeTrue()
        ->and($job->backoff())->toBe([5, 30, 120]);

});

test('a 5xx sequence fails retry attempts and succeeds on a later attempt', function (): void {
    Event::fake();
    Http::fakeSequence()
        ->push(['message' => 'provider unavailable'], 503)
        ->push(['message' => 'provider unavailable'], 503)
        ->push([], 202);
    $job = new SendVoucherSmsJob(Voucher::factory()->create());

    expect(fn () => $job->handle(app(SmsService::class)))->toThrow(RequestException::class);
    expect(fn () => $job->handle(app(SmsService::class)))->toThrow(RequestException::class);
    $job->handle(app(SmsService::class));
    expect($job->backoff())->toBe([5, 30, 120]);

    Http::assertSentCount(3);
});

test('a hanging fake endpoint is bounded by the client and job timeout configuration', function (): void {
    Http::fake(fn () => throw new ConnectionException('The SMS provider did not respond before the timeout.'));
    $job = new SendVoucherSmsJob(Voucher::factory()->create());

    expect(fn () => $job->handle(app(SmsService::class)))->toThrow(ConnectionException::class);
    expect(config('sms.fake.timeout'))->toBeLessThan($job->timeout)
        ->and(config('sms.fake.connect_timeout'))->toBeLessThanOrEqual(config('sms.fake.timeout'));
});
