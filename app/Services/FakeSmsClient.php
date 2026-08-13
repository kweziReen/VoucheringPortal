<?php

namespace App\Services;

use App\Contracts\SmsService;
use Illuminate\Support\Facades\Http;

class FakeSmsClient implements SmsService
{
    public function send(string $msisdn, string $message): void
    {
        Http::acceptJson()
            ->timeout(config('sms.fake.timeout'))
            ->connectTimeout(config('sms.fake.connect_timeout'))
            ->post(config('sms.fake.endpoint'), [
                'to' => $msisdn,
                'message' => $message,
            ])
            ->throw();
    }
}
