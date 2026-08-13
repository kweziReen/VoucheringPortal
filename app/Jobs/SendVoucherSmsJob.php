<?php

namespace App\Jobs;

use App\Contracts\SmsService;
use App\Models\Voucher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendVoucherSmsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /** The total number of attempts, including the first. */
    public int $tries = 4;

    /** Stop a stuck worker process after 15 seconds. */
    public int $timeout = 15;

    /** Mark a worker timeout as a failed attempt so it can be retried. */
    public bool $failOnTimeout = true;

    public function __construct(public Voucher $voucher) {}

    /**
     * Retry delays in seconds after attempts one, two, and three.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(SmsService $sms): void
    {
        $msisdn = $this->voucher->msisdn_encrypted;

        if ($msisdn === null) {
            throw new \RuntimeException('Cannot send an SMS without an MSISDN.');
        }

        $sms->send($msisdn, "Your voucher code is {$this->voucher->code}.");
    }
}
