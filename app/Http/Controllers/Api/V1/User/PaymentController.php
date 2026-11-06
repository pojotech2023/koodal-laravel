<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\Payment\CreateRazorpayOrder;
use App\Actions\Payment\VerifyRazorpayPayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreateOrderRequest;
use App\Http\Requests\Payment\VerifyPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Services\Payment\RazorpayClient;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function __construct(protected RazorpayClient $razorpay) {}

    /**
     * Everything the Razorpay checkout sheet needs to open, and nothing more.
     * The key returned is the public one — the secret never leaves the server,
     * and a client bundle is not a place a secret can hide.
     */
    public function createOrder(CreateOrderRequest $request, CreateRazorpayOrder $createOrder): JsonResponse
    {
        $user = $request->user();
        $payment = $createOrder($user, MembershipPlan::findOrFail($request->integer('plan_id')));

        return ApiResponse::created([
            'razorpay_order_id' => $payment->razorpay_order_id,
            'razorpay_key_id' => $this->razorpay->publicKey(),
            // Paise, because that is what the checkout SDK expects. This is the
            // one place outside RazorpayClient where the unit is not rupees.
            'amount' => $payment->amountInPaise(),
            'currency' => $payment->currency,
            'payment_id' => $payment->id,
            // Prefilled so the user is not asked for details we already hold.
            'prefill' => [
                'name' => $user->name,
                'email' => $user->email,
                'contact' => $user->mobile,
            ],
        ], 'Order created successfully');
    }

    /**
     * The client reports what the checkout told it. None of it is believed: the
     * signature is recomputed with our secret and the payment is confirmed
     * against Razorpay's API before a subscription exists.
     */
    public function verify(VerifyPaymentRequest $request, VerifyRazorpayPayment $verify): JsonResponse
    {
        $payment = $verify(
            orderId: $request->string('razorpay_order_id')->toString(),
            paymentId: $request->string('razorpay_payment_id')->toString(),
            signature: $request->string('razorpay_signature')->toString(),
        );

        $payment->load('subscription.plan');

        return ApiResponse::success([
            'subscription' => new SubscriptionResource($payment->subscription),
            'invoice_number' => $payment->invoice_number,
        ], 'Payment verified and membership activated');
    }

    public function index(Request $request): JsonResponse
    {
        $payments = $request->user()->payments()
            ->with('plan:id,name,slug,duration_days')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::paginated($payments, PaymentResource::class, 'Payments retrieved successfully');
    }

    /**
     * A short-lived signed URL, minted per request against the private disk.
     *
     * Never a permanent path: an invoice carries a name, an amount and a
     * billing address, and a guessable URL for one is a guessable URL for all
     * of them.
     */
    public function invoice(Payment $payment, Request $request): JsonResponse
    {
        // 404 rather than 403 — the id space is sequential, and confirming that
        // payment 4471 exists is information a stranger has no business having.
        abort_unless($payment->user_id === $request->user()->id, 404);

        abort_if($payment->invoice_path === null, 404, 'The invoice for this payment is not ready yet.');

        $minutes = config('matrimony.media.document.signed_url_ttl');

        return ApiResponse::success([
            'invoice_number' => $payment->invoice_number,
            'url' => Storage::disk('private')->temporaryUrl(
                $payment->invoice_path,
                now()->addMinutes($minutes),
            ),
            'expires_in_minutes' => $minutes,
        ], 'Invoice link generated');
    }
}
