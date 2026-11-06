<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\Membership\FeatureGate;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * `plans()` is public — the pricing page has to render before anyone signs
 * up, same as `Api\V1\User\MembershipController::plans()` — while `index()`
 * (the status card, quota meters and billing/subscription history) is behind
 * `auth:web` like the rest of the member area. One controller, two guards at
 * the route level, mirroring how `SearchController`/`ProfileController` split
 * public vs member work elsewhere in this app rather than the API's own
 * controller boundary.
 */
class MembershipController extends Controller
{
    public function __construct(protected FeatureGate $features) {}

    /**
     * The free plan is included rather than filtered out — a table that
     * starts at ₹1,999 with no "what you already have" column makes the
     * paid tiers look like the only option, and visitors bounce off it.
     */
    public function plans(Request $request): View
    {
        return view('membership.plans', [
            'plans' => MembershipPlan::active()->get(),
            'viewer' => $request->user()?->load('activeSubscription.plan'),
        ]);
    }

    /** The status card + quota meters + billing/subscription history. */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('membership.index', [
            'subscription' => $user->activeSubscription?->load('plan'),
            'usage' => $this->usageMap($user),
            'subscriptions' => $user->subscriptions()
                ->with('plan:id,name,slug,price')
                ->latest('starts_at')
                ->paginate(10, ['*'], 'subscriptions_page'),
            'payments' => $user->payments()
                ->with('plan:id,name,slug,duration_days')
                ->latest()
                ->paginate(10, ['*'], 'payments_page'),
        ]);
    }

    /**
     * Only quota features appear here — a capability is on or off and lives
     * in `features`; a meter for something that cannot be consumed would be
     * a progress bar with nothing to fill it. Same shape as the API's.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function usageMap(User $user): array
    {
        $usage = [];

        foreach (config('matrimony.membership.features') as $key => $schema) {
            if ($schema['type'] !== 'quota') {
                continue;
            }

            $limit = $this->features->limit($user, $key);
            $used = $this->features->used($user, $key);

            $usage[$key] = [
                'label' => $schema['label'],
                'used' => $used,
                'limit' => $limit,
                'unlimited' => $limit === -1,
                'remaining' => $limit === -1 ? null : max(0, $limit - $used),
                'period' => $schema['period'],
            ];
        }

        return $usage;
    }
}
