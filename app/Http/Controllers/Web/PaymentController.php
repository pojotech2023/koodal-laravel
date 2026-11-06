<?php

namespace App\Http\Controllers\Web;

use App\Actions\Payment\CreateRazorpayOrder;
use App\Actions\Payment\VerifyRazorpayPayment;
use App\Exceptions\PaymentVerificationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreateOrderRequest;
use App\Http\Requests\Payment\VerifyPaymentRequest;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Services\Payment\RazorpayClient;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The one `Web\*` controller whose two main actions answer JSON, not HTML —
 * per docs/05-website-guide.md §8: Razorpay Checkout is a browser-side JS
 * overlay regardless of backend stack, so `resources/js/checkout.js` talks to
 * `createOrder`/`verify` with `fetch()`, not a `<form>` submit, and needs a
 * JSON body back either way, success or failure.
 *
 * `CreateRazorpayOrder`'s `abort(422/503, …)` and `VerifyRazorpayPayment`'s
 * `PaymentVerificationException` both self-render as `back()->with('status',
 * …)` for a non-`api/*` request — correct for every other `Web\*` caller, but
 * a redirect response is not what a failed `fetch()` call is expecting. Both
 * are caught here and answered as JSON explicitly instead of left to
 * self-render, same reasoning as this session's other JSON-under-`Web\*`
 * exception — see PROGRESS.md §6.
 */
class PaymentController extends Controller
{
    public function __construct(protected RazorpayClient $razorpay) {}

    /**
     * Same order as the API's: priced from the plan row and opened with
     * Razorpay by `CreateRazorpayOrder`, which also refuses inactive, free and
     * downgrade plans. There is no order-less fallback — a payment that was
     * never tied to a server-side order cannot be verified afterwards.
     */
    public function createOrder(CreateOrderRequest $request, CreateRazorpayOrder $createOrder): JsonResponse
    {
        $user = $request->user();
        $plan = MembershipPlan::findOrFail($request->integer('plan_id'));

        try {
            $payment = $createOrder($user, $plan);
        } catch (HttpExceptionInterface $e) {
            $status = $e->getStatusCode();

            // A refusal (422) says why; a server-side failure gets the same
            // generic sentence the app shows, never what broke.
            $message = $status >= 500 || $e->getMessage() === ''
                ? ApiResponse::defaultMessage($status)
                : $e->getMessage();

            return response()->json(['message' => $message], $status);
        }

        return response()->json([
            'razorpay_order_id' => $payment->razorpay_order_id,
            'razorpay_key_id' => $this->razorpay->publicKey(),
            'amount' => $payment->amountInPaise(),
            'currency' => $payment->currency,
            'payment_id' => $payment->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'plan_duration' => $plan->duration_days,
            'prefill' => [
                'name' => $user->name,
                'email' => $user->email,
                'contact' => $user->mobile,
            ],
        ], 201);
    }

    /**
     * The client reports what the checkout told it. None of it is believed:
     * `VerifyRazorpayPayment` recomputes the signature with our secret and
     * confirms the capture and amount with Razorpay before a subscription
     * exists. A report without an order id and signature proves nothing, so it
     * activates nothing — the webhook still activates a real payment.
     */
    public function verify(VerifyPaymentRequest $request, VerifyRazorpayPayment $verify): JsonResponse
    {
        $orderId = (string) $request->input('razorpay_order_id');
        $signature = (string) $request->input('razorpay_signature');
        $unverified = 'This payment could not be verified. If money was deducted, please contact support.';

        if ($orderId === '' || $signature === '') {
            return response()->json(['message' => $unverified], 422);
        }

        try {
            $payment = $verify(
                orderId: $orderId,
                paymentId: $request->string('razorpay_payment_id')->toString(),
                signature: $signature,
            );
        } catch (PaymentVerificationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => $unverified], 422);
        }

        $planName = $payment->plan?->name ?? 'Membership';
        $message = "Payment successful! Your {$planName} membership is now active.";
        session()->flash('status', $message);

        return response()->json([
            'success' => true,
            'redirect' => route('membership.index'),
            'message' => $message,
        ]);
    }

    /**
     * A short-lived signed URL, minted per click and redirected to — never
     * embedded in the billing history page itself, same reasoning as the
     * API's `invoice()`: a URL good for more than the one click is a leak.
     */
    public function invoice(Payment $payment, Request $request): RedirectResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 404);
        abort_if($payment->invoice_path === null, 404, 'The invoice for this payment is not ready yet.');

        $minutes = config('matrimony.media.document.signed_url_ttl');

        return redirect()->away(
            Storage::disk('private')->temporaryUrl($payment->invoice_path, now()->addMinutes($minutes)),
        );
    }
}
