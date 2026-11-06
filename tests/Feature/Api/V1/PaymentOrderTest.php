<?php

namespace Tests\Feature\Api\V1;

use App\Models\MembershipPlan;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

class PaymentOrderTest extends TestCase
{
    use RefreshDatabase, SeedsCoreData;

    /**
     * Members get the generic 503 sentence on purpose (saying payments are
     * misconfigured costs trust); the payments log names the missing keys.
     */
    public function test_an_order_without_razorpay_keys_answers_the_generic_503(): void
    {
        $this->seedRoles();
        $this->seedPlans();
        Http::fake();
        config(['services.razorpay.key_id' => 'rzp_test_x', 'services.razorpay.key_secret' => '']);
        $this->actingAsMember();

        $this->postJson('/api/v1/payments/order', ['plan_id' => MembershipPlan::firstWhere('slug', 'silver')->id])
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Koodal is being updated. Please try again in a few minutes.');

        $this->assertSame(0, Payment::count());
        Http::assertNothingSent();
    }
}
