<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use App\Models\Plan;
use Illuminate\Support\Str;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $plans = Plan::pluck('id')->toArray();

        foreach (range(1, 10) as $i) {
            Coupon::create([
                'code' => strtoupper(Str::random(8)),
                'type' => fake()->randomElement(['percentage', 'fixed']),
                'value' => fake()->randomFloat(2, 5, 50), // 5.00 to 50.00
                'usage_limit' => fake()->numberBetween(50, 500),
                'used' => fake()->numberBetween(0, 49),
                'status' => fake()->randomElement(['active', 'inactive']),
                'expires_at' => now()->addDays(fake()->numberBetween(10, 60)),
                'plan_id' => fake()->randomElement($plans),
            ]);
        }
    }
}
