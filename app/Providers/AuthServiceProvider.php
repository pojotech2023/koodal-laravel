<?php

namespace App\Providers;

use App\Models\ContactEnquiry;
use App\Models\Conversation;
use App\Models\Interest;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\ProfilePhoto;
use App\Models\Report;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\VerificationDocument;
use App\Policies\ContactEnquiryPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\InterestPolicy;
use App\Policies\MembershipPlanPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProfilePhotoPolicy;
use App\Policies\ReportPolicy;
use App\Policies\UserPolicy;
use App\Policies\UserProfilePolicy;
use App\Policies\VerificationDocumentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        ContactEnquiry::class => ContactEnquiryPolicy::class,
        Conversation::class => ConversationPolicy::class,
        Interest::class => InterestPolicy::class,
        MembershipPlan::class => MembershipPlanPolicy::class,
        Payment::class => PaymentPolicy::class,
        ProfilePhoto::class => ProfilePhotoPolicy::class,
        Report::class => ReportPolicy::class,
        User::class => UserPolicy::class,
        UserProfile::class => UserProfilePolicy::class,
        VerificationDocument::class => VerificationDocumentPolicy::class,
    ];

    public function boot(): void
    {
        /*
        |----------------------------------------------------------------------
        | Permission gates
        |----------------------------------------------------------------------
        | Every seeded permission name becomes a Gate, so a controller can write
        | $this->authorize('users.suspend') for coarse checks while Policies
        | handle the per-record questions a permission name cannot answer.
        |
        | Resolved lazily: this runs on every request and must not query the
        | permissions table to boot.
        */

        Gate::before(function (User $user, string $ability) {
            // A super admin bypasses permission checks but NOT Policies that
            // deny on business grounds (you still cannot refund twice).
            return $user->isSuperAdmin() && str_contains($ability, '.') ? true : null;
        });

        // `mixed`, not `?bool`. Laravel hands this callback whatever the Policy
        // returned, and a Policy that answers with an
        // Illuminate\Auth\Access\Response — which is the only way to get
        // `denyAsNotFound()`, and this codebase needs 404-not-403 on every
        // record a stranger could enumerate — would TypeError against `?bool`.
        Gate::after(function (User $user, string $ability, mixed $result) {
            return $result ?? (str_contains($ability, '.') ? $user->hasPermission($ability) : null);
        });
    }
}
