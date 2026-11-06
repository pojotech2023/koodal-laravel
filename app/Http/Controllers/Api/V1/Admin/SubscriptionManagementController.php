<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Subscription\CancelSubscription;
use App\Actions\Subscription\ExtendSubscription;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelSubscriptionRequest;
use App\Http\Requests\Admin\ExtendSubscriptionRequest;
use App\Http\Resources\Admin\AdminSubscriptionResource;
use App\Models\Subscription;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('subscriptions.view');

        $request->validate([
            'status' => ['nullable', 'in:pending,active,expired,cancelled,superseded'],
            'plan_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'expiring_in' => ['nullable', 'integer', 'min:1', 'max:90'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $subscriptions = Subscription::query()
            ->with([
                'user:id,profile_code,name,email,mobile,status',
                'plan:id,name,slug,price,duration_days,features',
                'payment:id,amount,status,invoice_number',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->integer('plan_id'), fn ($q, $id) => $q->where('membership_plan_id', $id))
            ->when($request->integer('user_id'), fn ($q, $id) => $q->where('user_id', $id))

            // The renewals worklist. `scopeExpiring` is the same window
            // `SendExpiryReminders` uses, so what support sees here is exactly
            // the set that has been or is about to be nudged.
            ->when($request->integer('expiring_in'), fn ($q, $days) => $q->expiring($days))
            ->latest('id')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::paginated($subscriptions, AdminSubscriptionResource::class, 'Subscriptions retrieved successfully');
    }

    public function extend(ExtendSubscriptionRequest $request, Subscription $subscription, ExtendSubscription $extend): JsonResponse
    {
        $this->authorize('subscriptions.manage');

        $subscription = $extend(
            $subscription,
            $request->integer('days'),
            $request->user(),
            $request->input('reason'),
        );

        return ApiResponse::success(
            new AdminSubscriptionResource($subscription->load('user', 'plan')),
            'Membership extended',
        );
    }

    public function cancel(CancelSubscriptionRequest $request, Subscription $subscription, CancelSubscription $cancel): JsonResponse
    {
        $this->authorize('subscriptions.manage');

        $subscription = $cancel($subscription, $request->user(), $request->string('reason')->toString());

        return ApiResponse::success(
            new AdminSubscriptionResource($subscription->load('user', 'plan')),
            'Membership cancelled. This does not refund anything — use the payment’s refund action for that.',
        );
    }
}
