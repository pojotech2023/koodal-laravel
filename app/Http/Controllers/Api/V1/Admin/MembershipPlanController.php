<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MembershipPlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\MembershipPlan;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Plan CRUD.
 *
 * Editing a plan does **not** reach backwards into what anyone bought:
 * `subscriptions.features_snapshot` freezes the feature map at purchase, and
 * `CacheBustingObserver` deliberately does not flush per-user feature caches
 * when a plan changes. Repricing tomorrow's sales is the intended operation;
 * silently taking a capability away from thousands of people who already paid
 * for it is not, and it is what a live-resolved plan would do.
 *
 * Deletion is refused by the Policy for any plan with subscribers and for the
 * default plan. Deactivation is what was actually wanted, and it is an update.
 */
class MembershipPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MembershipPlan::class);

        $plans = MembershipPlan::query()
            ->withCount(['subscriptions' => fn ($q) => $q->where('status', 'active')])
            // Inactive plans are included: this is the management list, and a
            // retired plan people still hold has to be visible to the person
            // deciding whether to bring it back.
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return ApiResponse::success(
            PlanResource::collection($plans)->resolve(),
            'Plans retrieved successfully',
        );
    }

    public function store(MembershipPlanRequest $request, AuditLogger $audit): JsonResponse
    {
        $this->authorize('create', MembershipPlan::class);

        $plan = MembershipPlan::create($request->validated());

        $audit->log('plan.created', $plan, newValues: $plan->getAttributes(), actor: $request->user());

        return ApiResponse::created(new PlanResource($plan), 'Plan created');
    }

    public function show(MembershipPlan $membershipPlan): JsonResponse
    {
        $this->authorize('view', $membershipPlan);

        $membershipPlan->loadCount(['subscriptions' => fn ($q) => $q->where('status', 'active')]);

        return ApiResponse::success(new PlanResource($membershipPlan), 'Plan retrieved successfully');
    }

    public function update(MembershipPlanRequest $request, MembershipPlan $membershipPlan, AuditLogger $audit): JsonResponse
    {
        $this->authorize('update', $membershipPlan);

        $membershipPlan->fill($request->validated());
        $membershipPlan->save();

        // The plan diff is the most consequential edit in the admin surface —
        // a price change or a quota change is read back months later when
        // someone asks why a cohort got what it got — so it goes in the trail
        // field by field.
        $audit->logChanges('plan.updated', $membershipPlan, 'Plan edited', $request->user());

        return ApiResponse::success(new PlanResource($membershipPlan->fresh()), 'Plan updated');
    }

    public function destroy(Request $request, MembershipPlan $membershipPlan, AuditLogger $audit): JsonResponse
    {
        $this->authorize('delete', $membershipPlan);

        $audit->log(
            'plan.deleted',
            $membershipPlan,
            oldValues: $membershipPlan->getAttributes(),
            description: 'Plan deleted',
            actor: $request->user(),
        );

        // Soft delete: `payments.membership_plan_id` and the invoice template
        // both name the plan, and a hard delete would leave historical
        // invoices pointing at nothing.
        $membershipPlan->delete();

        return ApiResponse::success(null, 'Plan deleted');
    }
}
