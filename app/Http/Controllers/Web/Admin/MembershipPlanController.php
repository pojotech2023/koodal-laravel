<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MembershipPlanFormRequest;
use App\Models\MembershipPlan;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * MembershipPlanController} — same `MembershipPlanPolicy` abilities, same
 * audit calls (including `logChanges` on update, which is what makes a price
 * change readable months later), same soft delete.
 *
 * The rule this module exists to respect: **editing a plan does not reach
 * backwards into what anyone bought.** `subscriptions.features_snapshot`
 * freezes the feature map at purchase, so repricing tomorrow's sales is the
 * intended operation and silently withdrawing a capability from people who
 * already paid is not. The editor says so on the page rather than leaving an
 * admin to discover it.
 *
 * Validation comes from `MembershipPlanFormRequest` — the shared
 * `MembershipPlanRequest` with one encoding shim, because an HTML form posts
 * strings where the API posts typed JSON. See that class.
 */
class MembershipPlanController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', MembershipPlan::class);

        $plans = MembershipPlan::query()
            ->withCount(['subscriptions' => fn ($q) => $q->where('status', 'active')])

            // Inactive plans included: this is the management list, and a
            // retired plan people still hold has to be visible to whoever is
            // deciding whether to bring it back.
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('admin.plans.index', ['plans' => $plans]);
    }

    public function create(): View
    {
        $this->authorize('create', MembershipPlan::class);

        return view('admin.plans.form', [
            // `services.razorpay.currency` is where the app's currency is
            // configured — the same value `CreateRazorpayOrder` sends — so a
            // new plan defaults to it rather than to a second literal 'INR'
            // that could drift from the gateway's.
            'plan' => new MembershipPlan(['features' => [], 'currency' => config('services.razorpay.currency', 'INR')]),
            'schema' => config('matrimony.membership.features', []),
        ]);
    }

    public function store(MembershipPlanFormRequest $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', MembershipPlan::class);

        $plan = MembershipPlan::create($request->validated());

        $audit->log('plan.created', $plan, newValues: $plan->getAttributes(), actor: $request->user());

        return redirect()->route('admin.plans.index')->with('status', "Plan “{$plan->name}” created.");
    }

    public function edit(MembershipPlan $membershipPlan): View
    {
        $this->authorize('update', $membershipPlan);

        $membershipPlan->loadCount(['subscriptions' => fn ($q) => $q->where('status', 'active')]);

        return view('admin.plans.form', [
            'plan' => $membershipPlan,
            'schema' => config('matrimony.membership.features', []),
        ]);
    }

    public function update(MembershipPlanFormRequest $request, MembershipPlan $membershipPlan, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $membershipPlan);

        $membershipPlan->fill($request->validated());
        $membershipPlan->save();

        // Field by field: the plan diff is the most consequential edit in the
        // admin surface, and it is read back months later when someone asks
        // why a cohort got what it got.
        $audit->logChanges('plan.updated', $membershipPlan, 'Plan edited', $request->user());

        return redirect()->route('admin.plans.index')->with('status', "Plan “{$membershipPlan->name}” updated. Existing subscriptions keep what they bought.");
    }

    public function destroy(Request $request, MembershipPlan $membershipPlan, AuditLogger $audit): RedirectResponse
    {
        // The Policy refuses the default plan and any plan with subscribers —
        // deactivation is what was actually wanted there, and it is an update.
        $this->authorize('delete', $membershipPlan);

        $audit->log(
            'plan.deleted',
            $membershipPlan,
            oldValues: $membershipPlan->getAttributes(),
            description: 'Plan deleted',
            actor: $request->user(),
        );

        // Soft delete: `payments.membership_plan_id` and the invoice template
        // both name the plan, and a hard delete leaves historical invoices
        // pointing at nothing.
        $membershipPlan->delete();

        return redirect()->route('admin.plans.index')->with('status', 'Plan deleted. Past invoices still name it.');
    }
}
