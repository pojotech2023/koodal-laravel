<?php

namespace Tests\Feature\Web;

use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SeedsCoreData;
use Tests\TestCase;

/**
 * The website's checkout used to activate a plan on the browser's word when
 * the Razorpay signature was missing ("client-side checkout"), so any member
 * could post a made-up payment id and get Gold. Only a server-side order and
 * a signature over it activate anything now.
 */
class PaymentCheckoutWebTest extends TestCase
{
    use RefreshDatabase, SeedsCoreData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPlans();
        Http::fake();
    }

    public function test_verify_without_a_signature_activates_nothing(): void
    {
        config(['services.razorpay.key_id' => 'rzp_test_x', 'services.razorpay.key_secret' => 'secret']);
        $me = $this->member();
        $this->actingAs($me, 'web');
        $payment = $this->openOrder($me->id);

        $this->postJson('/payments/verify', [
            'plan_id' => $payment->membership_plan_id,
            'payment_id' => $payment->id,
            'razorpay_order_id' => $payment->razorpay_order_id,
            'razorpay_payment_id' => 'pay_made_up',
        ])->assertUnprocessable();

        $this->assertSame('created', $payment->refresh()->status);
        $this->assertSame(0, Subscription::where('user_id', $me->id)->count());
        Http::assertNothingSent();
    }

    public function test_verify_without_keys_or_an_order_activates_nothing(): void
    {
        config(['services.razorpay.key_secret' => '']);
        $me = $this->member();
        $this->actingAs($me, 'web');

        $this->postJson('/payments/verify', [
            'plan_id' => MembershipPlan::firstWhere('slug', 'gold')->id,
            'razorpay_payment_id' => 'pay_made_up',
        ])->assertUnprocessable();

        $this->assertSame(0, Payment::count());
        $this->assertSame(0, Subscription::where('user_id', $me->id)->count());
    }

    public function test_verify_with_a_forged_signature_is_refused(): void
    {
        config(['services.razorpay.key_id' => 'rzp_test_x', 'services.razorpay.key_secret' => 'secret']);
        $me = $this->member();
        $this->actingAs($me, 'web');
        $payment = $this->openOrder($me->id);

        $this->postJson('/payments/verify', [
            'razorpay_order_id' => $payment->razorpay_order_id,
            'razorpay_payment_id' => 'pay_made_up',
            'razorpay_signature' => str_repeat('0', 64),
        ])->assertUnprocessable()->assertJsonPath('message', 'This payment could not be verified.');

        $this->assertSame(0, Subscription::where('user_id', $me->id)->count());
    }

    public function test_an_order_without_keys_answers_the_generic_503(): void
    {
        config(['services.razorpay.key_secret' => '']);
        $this->actingAs($this->member(), 'web');

        $this->postJson('/payments/order', ['plan_id' => MembershipPlan::firstWhere('slug', 'silver')->id])
            ->assertStatus(503)
            ->assertJsonPath('message', 'Koodal is being updated. Please try again in a few minutes.');

        $this->assertSame(0, Payment::count());
        Http::assertNothingSent();
    }

    protected function openOrder(int $userId): Payment
    {
        $plan = MembershipPlan::firstWhere('slug', 'gold');

        return Payment::create([
            'user_id' => $userId,
            'membership_plan_id' => $plan->id,
            'razorpay_order_id' => 'order_test_1',
            'amount' => $plan->price,
            'currency' => 'INR',
            'status' => 'created',
        ]);
    }
}
