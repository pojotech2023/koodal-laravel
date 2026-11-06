<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Subscription\CancelSubscription;
use App\Actions\Subscription\ExtendSubscription;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelSubscriptionRequest;
use App\Http\Requests\Admin\ExtendSubscriptionRequest;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * SubscriptionManagementController} — same query, same string abilities
 * (`subscriptions.view` / `subscriptions.manage`, resolved by
 * `AuthServiceProvider`'s `Gate::after` against `hasPermission`), same two
 * Actions.
 *
 * The detail page exists here and not in the API because of one thing the
 * panel can show and a JSON list cannot usefully: `features_snapshot`, the
 * feature map frozen at purchase. That is what `FeatureGate` actually reads
 * for this member — not the plan's current features — so when someone calls to
 * say a capability they paid for has gone, the snapshot is the answer, and it
 * needs to be readable side by side with what the plan grants today.
 */
class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('subscriptions.view');

        $request->validate([
            'status' => ['nullable', 'in:pending,active,expired,cancelled,superseded'],
            'plan_id' => ['nullable', 'integer'],
            'expiring_in' => ['nullable', 'integer', 'min:1', 'max:90'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $subscriptions = Subscription::query()
            ->with([
                'user:id,profile_code,name,email,mobile,status',
                'plan:id,name,slug,price,duration_days',
                'payment:id,amount,status,invoice_number',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->integer('plan_id'), fn ($q, $id) => $q->where('membership_plan_id', $id))
            ->when($request->filled('q'), fn ($q) => $q->whereHas(
                'user',
                fn ($u) => $u->where('profile_code', strtoupper(trim($request->string('q')->toString())))
                    ->orWhere('name', 'like', trim($request->string('q')->toString()).'%'),
            ))

            // The renewals worklist. `scopeExpiring` is the same window
            // `SendExpiryReminders` uses, so what support sees here is exactly
            // the set that has been or is about to be nudged.
            ->when($request->integer('expiring_in'), fn ($q, $days) => $q->expiring($days))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'plans' => MembershipPlan::query()->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function show(Subscription $subscription): View
    {
        $this->authorize('subscriptions.view');

        $subscription->load([
            'user:id,profile_code,name,email,mobile,status',
            'plan',
            'payment',
        ]);

        return view('admin.subscriptions.show', [
            'subscription' => $subscription,
            'schema' => config('matrimony.membership.features', []),
        ]);
    }

    public function extend(ExtendSubscriptionRequest $request, Subscription $subscription, ExtendSubscription $extend): RedirectResponse
    {
        $this->authorize('subscriptions.manage');

        $extend(
            $subscription,
            $request->integer('days'),
            $request->user(),
            $request->input('reason'),
        );

        return back()->with('status', "Membership extended by {$request->integer('days')} days.");
    }

    public function cancel(CancelSubscriptionRequest $request, Subscription $subscription, CancelSubscription $cancel): RedirectResponse
    {
        $this->authorize('subscriptions.manage');

        $cancel($subscription, $request->user(), $request->string('reason')->toString());

        // Said plainly, because the two are separate systems and assuming
        // otherwise is how a member ends up cancelled and not refunded.
        return back()->with('status', 'Membership cancelled. Nothing has been refunded — use the payment’s refund action for that.');
    }
}
