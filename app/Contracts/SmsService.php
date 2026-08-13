<?php

namespace App\Contracts;

interface SmsService
{
    public function send(string $msisdn, string $message): void;
}
