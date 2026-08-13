<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Voucher> */
class VoucherFactory extends Factory
{
    protected $model = Voucher::class;

    public function definition(): array
    {
        $msisdn = fake()->e164PhoneNumber();

        return [
            'campaign_id' => Campaign::factory(),
            'code' => Str::upper(fake()->unique()->bothify('???-####-???')),
            'msisdn_hash' => hash('sha256', $msisdn),
            'msisdn_encrypted' => $msisdn,
            'issued_at' => now(),
            'redeemed_at' => null,
        ];
    }
}
