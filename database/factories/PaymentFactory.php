<?php

namespace Database\Factories;

use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'membership_plan_id' => MembershipPlan::factory(),
            'razorpay_order_id' => 'order_'.fake()->unique()->lexify('??????????????'),
            'amount' => 4999,
            'currency' => 'INR',
            'status' => 'created',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => 'paid',
            'razorpay_payment_id' => 'pay_'.fake()->unique()->lexify('??????????????'),
            'method' => 'upi',
            'verified_at' => now(),
        ]);
    }
}
