<?php

namespace Database\Factories;

use App\Models\Redemption;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Redemption> */
class RedemptionFactory extends Factory
{
    protected $model = Redemption::class;

    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'redeemed_at' => now(),
        ];
    }
}
