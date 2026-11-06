<?php

use App\Http\Controllers\Api\V1\Admin\AdminAuthController;
use App\Http\Controllers\Api\V1\Admin\AnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use App\Http\Controllers\Api\V1\Admin\ChatModerationController;
use App\Http\Controllers\Api\V1\Admin\CmsManagementController;
use App\Http\Controllers\Api\V1\Admin\ContactEnquiryController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\ExportController;
use App\Http\Controllers\Api\V1\Admin\InterestManagementController;
use App\Http\Controllers\Api\V1\Admin\LocationManagementController;
use App\Http\Controllers\Api\V1\Admin\MasterDataManagementController;
use App\Http\Controllers\Api\V1\Admin\MembershipPlanController;
use App\Http\Controllers\Api\V1\Admin\NotificationLogController;
use App\Http\Controllers\Api\V1\Admin\PaymentManagementController;
use App\Http\Controllers\Api\V1\Admin\PhotoModerationController;
use App\Http\Controllers\Api\V1\Admin\ProfileModerationController;
use App\Http\Controllers\Api\V1\Admin\ReportManagementController;
use App\Http\Controllers\Api\V1\Admin\SettingsController;
use App\Http\Controllers\Api\V1\Admin\StaffController;
use App\Http\Controllers\Api\V1\Admin\SubscriptionManagementController;
use App\Http\Controllers\Api\V1\Admin\UserManagementController;
use App\Http\Controllers\Api\V1\Admin\VerificationManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
| Three gates, each doing a different job:
|
|   abilities:admin   — the token was issued to staff, not a member
|   role:...          — coarse gate on whole sections
|   Policies          — per-record checks inside the controllers
|
| Route-level gating alone is never enough: hiding a control in the UI is a
| courtesy, and a role gate does not know whether *this* admin may act on
| *this* record. Every mutating endpoint here writes an audit_logs row.
*/

Route::prefix('admin')->group(function () {

    Route::post('login', [AdminAuthController::class, 'login'])->middleware('throttle:auth');

    Route::middleware(['auth:sanctum', 'abilities:admin', 'role:super_admin,admin,moderator,support'])->group(function () {

        Route::get('me', [AdminAuthController::class, 'me']);
        Route::post('logout', [AdminAuthController::class, 'logout']);

        /*
        |------------------------------------------------------------------
        | Dashboard & analytics
        |------------------------------------------------------------------
        | Pre-aggregated server-side. The client never aggregates raw rows.
        */

        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('dashboard/charts/{chart}', [DashboardController::class, 'chart'])
            ->where('chart', 'registrations|revenue|subscriptions|demographics|active-users|locations');

        Route::get('analytics/{report}', AnalyticsController::class)
            ->where('report', 'users|revenue|engagement|funnel');

        /*
        |------------------------------------------------------------------
        | Members
        |------------------------------------------------------------------
        | Exports are queued — a 128k-row CSV is a job, not a request.
        */

        Route::get('users', [UserManagementController::class, 'index']);
        Route::get('users/export', [UserManagementController::class, 'export']);
        Route::get('users/{user}', [UserManagementController::class, 'show']);
        Route::put('users/{user}', [UserManagementController::class, 'update']);
        Route::patch('users/{user}/{action}', [UserManagementController::class, 'action'])
            ->where('action', 'suspend|activate|block|unblock|verify');
        Route::delete('users/{user}', [UserManagementController::class, 'destroy']);

        Route::get('profiles/pending', [ProfileModerationController::class, 'pending']);
        Route::patch('profiles/{profile}/approve', [ProfileModerationController::class, 'approve']);
        Route::patch('profiles/{profile}/reject', [ProfileModerationController::class, 'reject']);

        Route::get('photos/pending', [PhotoModerationController::class, 'pending']);
        Route::patch('photos/bulk', [PhotoModerationController::class, 'bulk']);
        Route::patch('photos/{photo}/approve', [PhotoModerationController::class, 'approve']);
        Route::patch('photos/{photo}/reject', [PhotoModerationController::class, 'reject']);

        /*
        |------------------------------------------------------------------
        | Verification
        |------------------------------------------------------------------
        | The document endpoint mints a 5-minute signed URL and writes an audit
        | row per view. Deliberately one document at a time — there is no bulk
        | download, because a bulk export of government IDs is the worst thing
        | this system could offer.
        */

        Route::get('verifications', [VerificationManagementController::class, 'index']);
        Route::get('verifications/{document}/document', [VerificationManagementController::class, 'viewDocument']);
        Route::patch('verifications/{document}/{action}', [VerificationManagementController::class, 'action'])
            ->where('action', 'approve|reject|resubmit');

        /*
        |------------------------------------------------------------------
        | Interests
        |------------------------------------------------------------------
        | Read-only, both of them. There is no endpoint that sends, answers or
        | deletes an interest on a member's behalf — an interest is a statement
        | one person made about another, and staff editing it would forge
        | consent. `stats` is registered before the collection route so the
        | literal segment is not swallowed by a future `{interest}` binding.
        */

        Route::get('interests/stats', [InterestManagementController::class, 'stats']);
        Route::get('interests', [InterestManagementController::class, 'index']);

        /*
        |------------------------------------------------------------------
        | Commerce
        |------------------------------------------------------------------
        */

        Route::apiResource('membership-plans', MembershipPlanController::class);

        Route::get('subscriptions', [SubscriptionManagementController::class, 'index']);
        Route::patch('subscriptions/{subscription}/extend', [SubscriptionManagementController::class, 'extend']);
        Route::patch('subscriptions/{subscription}/cancel', [SubscriptionManagementController::class, 'cancel']);

        Route::get('payments', [PaymentManagementController::class, 'index']);
        Route::get('payments/export', [PaymentManagementController::class, 'export']);
        Route::get('payments/{payment}', [PaymentManagementController::class, 'show']);
        Route::post('payments/{payment}/refund', [PaymentManagementController::class, 'refund']);

        /*
        |------------------------------------------------------------------
        | Export downloads
        |------------------------------------------------------------------
        | The other half of `users/export` and `payments/export`, which queue a
        | job and return nothing fetchable. `{notification}` is the
        | `export_ready` row the job wrote: the client names a notification, not
        | a path, so there is no path to sanitise — the private disk also holds
        | government IDs. Scoped to the caller's own exports, permission
        | re-checked at download time, every download audited.
        */

        Route::get('exports', [ExportController::class, 'index']);
        Route::get('exports/{notification}/download', [ExportController::class, 'download']);

        /*
        |------------------------------------------------------------------
        | Trust & safety
        |------------------------------------------------------------------
        | Chat moderation is a real privacy trade-off. It is kept narrow on
        | purpose: read-only, one conversation at a time, no cross-conversation
        | search, no export, and every access audited.
        */

        Route::get('reports', [ReportManagementController::class, 'index']);
        Route::patch('reports/{report}/assign', [ReportManagementController::class, 'assign']);
        Route::patch('reports/{report}/resolve', [ReportManagementController::class, 'resolve']);
        Route::patch('reports/{report}/dismiss', [ReportManagementController::class, 'dismiss']);

        Route::get('conversations', [ChatModerationController::class, 'index']);
        Route::get('conversations/{conversation}/messages', [ChatModerationController::class, 'messages']);

        /*
        |------------------------------------------------------------------
        | Content & configuration
        |------------------------------------------------------------------
        */

        Route::post('notifications/broadcast', [CmsManagementController::class, 'broadcast']);

        // The delivery log — is the notification pipeline working. Metadata
        // only: message notifications carry a chat preview in `body`, so
        // returning bodies here would be a cross-conversation chat search
        // behind a permission far broader than `chat.moderate`. See
        // AdminNotificationResource.
        Route::get('notifications', NotificationLogController::class);

        /*
        |------------------------------------------------------------------
        | Contact enquiries
        |------------------------------------------------------------------
        | The reading end of the public contact form. Its own `enquiries.*`
        | permissions rather than `cms.*`: the row arrives via the CMS
        | controller, but answering it is support's work and the support role
        | holds no CMS permission.
        */

        Route::get('enquiries', [ContactEnquiryController::class, 'index']);
        Route::get('enquiries/{enquiry}', [ContactEnquiryController::class, 'show']);
        Route::patch('enquiries/{enquiry}/{action}', [ContactEnquiryController::class, 'action'])
            ->where('action', 'claim|respond|close');

        Route::apiResource('cms/pages', CmsManagementController::class);
        Route::apiResource('cms/faqs', CmsManagementController::class);
        Route::apiResource('cms/banners', CmsManagementController::class);
        Route::apiResource('cms/success-stories', CmsManagementController::class);

        Route::get('master-data/{type}', [MasterDataManagementController::class, 'index']);
        Route::post('master-data/{type}', [MasterDataManagementController::class, 'store']);
        Route::put('master-data/{type}/{id}', [MasterDataManagementController::class, 'update']);
        Route::delete('master-data/{type}/{id}', [MasterDataManagementController::class, 'destroy']);

        Route::get('locations/{type}', [LocationManagementController::class, 'index']);
        Route::post('locations/{type}', [LocationManagementController::class, 'store']);
        Route::post('locations/import', [LocationManagementController::class, 'import']);
        Route::put('locations/{type}/{id}', [LocationManagementController::class, 'update']);
        Route::delete('locations/{type}/{id}', [LocationManagementController::class, 'destroy']);

        /*
        |------------------------------------------------------------------
        | Staff, roles, settings, audit — Super Admin territory
        |------------------------------------------------------------------
        */

        Route::middleware('role:super_admin')->group(function () {
            Route::apiResource('staff', StaffController::class);
            Route::get('roles', [StaffController::class, 'roles']);
            Route::put('roles/{role}', [StaffController::class, 'updateRole']);
            Route::get('permissions', [StaffController::class, 'permissions']);
        });

        Route::get('settings', [SettingsController::class, 'index']);
        Route::put('settings', [SettingsController::class, 'update']);

        Route::get('audit-logs', AuditLogController::class);
    });
});
