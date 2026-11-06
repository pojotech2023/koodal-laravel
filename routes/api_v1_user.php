<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\DeviceTokenController;
use App\Http\Controllers\Api\V1\User\BlockController;
use App\Http\Controllers\Api\V1\User\ChatConfigController;
use App\Http\Controllers\Api\V1\User\ChatQuickRepliesController;
use App\Http\Controllers\Api\V1\User\ConversationController;
use App\Http\Controllers\Api\V1\User\InterestController;
use App\Http\Controllers\Api\V1\User\JadhagamController;
use App\Http\Controllers\Api\V1\User\MatchController;
use App\Http\Controllers\Api\V1\User\MembershipController;
use App\Http\Controllers\Api\V1\User\MessageController;
use App\Http\Controllers\Api\V1\User\NotificationController;
use App\Http\Controllers\Api\V1\User\PaymentController;
use App\Http\Controllers\Api\V1\User\PreferenceController;
use App\Http\Controllers\Api\V1\User\PrivacyController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\User\ProfilePhotoController;
use App\Http\Controllers\Api\V1\User\ProfileViewController;
use App\Http\Controllers\Api\V1\User\PublicProfileController;
use App\Http\Controllers\Api\V1\User\ReportController;
use App\Http\Controllers\Api\V1\User\SavedSearchController;
use App\Http\Controllers\Api\V1\User\SearchController;
use App\Http\Controllers\Api\V1\User\ShortlistController;
use App\Http\Controllers\Api\V1\User\VerificationController;
use App\Http\Controllers\Api\V1\User\Games\FindItController;
use App\Http\Controllers\Api\V1\User\Games\FlipController;
use App\Http\Controllers\Api\V1\User\Games\HubController;
use App\Http\Controllers\Api\V1\User\Games\InviteController as GameInviteController;
use App\Http\Controllers\Api\V1\User\Games\MemorySnapController;
use App\Http\Controllers\Api\V1\User\Games\RoomController;
use App\Http\Controllers\Api\V1\User\Games\SoloController;
use App\Http\Controllers\Api\V1\User\Games\SpotController;
use App\Http\Controllers\Api\V1\User\Games\SyncController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authenticated member routes
|--------------------------------------------------------------------------
| `abilities:user` keeps a member token off the admin surface even if the URL
| is guessed. EnsureActiveUser bounces suspended and deactivated accounts;
| RecordUserActivity keeps last_active_at fresh, which powers the "recently
| active" sort and presence.
*/

Route::middleware(['auth:sanctum', 'abilities:user', 'active.user', 'record.activity'])->group(function () {

    /*
    |----------------------------------------------------------------------
    | Session
    |----------------------------------------------------------------------
    */

    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);

    Route::post('auth/device-token', [DeviceTokenController::class, 'store']);
    Route::delete('auth/device-token', [DeviceTokenController::class, 'destroy']);
    Route::post('fcm-token/store', [\App\Http\Controllers\Api\V1\Auth\FcmTokenController::class, 'store']);
    Route::post('fcm-token/remove', [\App\Http\Controllers\Api\V1\Auth\FcmTokenController::class, 'remove']);

    /*
    |----------------------------------------------------------------------
    | Own profile
    |----------------------------------------------------------------------
    | Sectioned PUTs rather than one fat endpoint: the app edits one card at a
    | time, validation messages map to the visible form, and a partial save can
    | never blank a section the user did not open.
    */

    Route::get('profile', [ProfileController::class, 'show']);
    Route::get('profile/completion', [ProfileController::class, 'completion']);
    Route::get('profile/export', [ProfileController::class, 'export']);

    foreach (['basic', 'religious', 'education', 'professional', 'family', 'lifestyle', 'about', 'location'] as $section) {
        Route::put("profile/{$section}", [ProfileController::class, 'update'.ucfirst($section)]);
    }

    Route::post('profile/deactivate', [ProfileController::class, 'deactivate']);
    Route::post('profile/reactivate', [ProfileController::class, 'reactivate']);
    Route::delete('profile', [ProfileController::class, 'destroy']);

    Route::get('profile-photos', [ProfilePhotoController::class, 'index']);
    Route::post('profile-photos', [ProfilePhotoController::class, 'store'])->middleware('throttle:20,60');
    Route::patch('profile-photos/reorder', [ProfilePhotoController::class, 'reorder']);
    Route::patch('profile-photos/{photo}/primary', [ProfilePhotoController::class, 'setPrimary']);
    Route::delete('profile-photos/{photo}', [ProfilePhotoController::class, 'destroy']);

    Route::get('profile/jadhagams', [JadhagamController::class, 'index']);
    Route::post('profile/jadhagams', [JadhagamController::class, 'store'])->middleware('throttle:20,60');
    Route::delete('profile/jadhagams/{jadhagam}', [JadhagamController::class, 'destroy'])->whereNumber('jadhagam');

    Route::get('verification', [VerificationController::class, 'index']);
    Route::post('verification', [VerificationController::class, 'store'])->middleware('throttle:5,60');
    Route::post('profile/aadhaar/generate-otp', [VerificationController::class, 'generateAadhaarOtp'])->middleware('throttle:otp');
    Route::post('profile/aadhaar/verify-otp', [VerificationController::class, 'verifyAadhaarOtp'])->middleware('throttle:otp');

    Route::get('preferences', [PreferenceController::class, 'show']);
    Route::put('preferences', [PreferenceController::class, 'update']);

    Route::get('privacy', [PrivacyController::class, 'show']);
    Route::put('privacy', [PrivacyController::class, 'update']);

    /*
    |----------------------------------------------------------------------
    | Discovery
    |----------------------------------------------------------------------
    | Gender is never a parameter — the backend inverts it from the
    | authenticated user, so a client cannot search its own gender.
    */

    Route::middleware('throttle:search')->group(function () {
        Route::get('search', SearchController::class);
        Route::get('search/quick', [SearchController::class, 'quick']);
        Route::get('search/filters', [SearchController::class, 'filters']);
    });

    Route::get('saved-searches', [SavedSearchController::class, 'index']);
    Route::post('saved-searches', [SavedSearchController::class, 'store']);
    Route::delete('saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy']);

    Route::get('matches', [MatchController::class, 'index']);
    Route::get('matches/new', [MatchController::class, 'newProfiles']);
    Route::get('matches/recently-active', [MatchController::class, 'recentlyActive']);
    Route::get('matches/mutual', [MatchController::class, 'mutual']);
    Route::get('matches/near-me', [MatchController::class, 'nearMe']);
    Route::get('matches/filter-options', [MatchController::class, 'filterOptions']);
    Route::get('matches/preference-match', [MatchController::class, 'preferenceMatch']);
    Route::get('matches/caste-match', [MatchController::class, 'casteMatch']);

    /*
    |----------------------------------------------------------------------
    | Viewing other profiles
    |----------------------------------------------------------------------
    | Every response here passes through ProfileVisibility. Fields the viewer
    | may not see are absent from the JSON, not null — a client cannot leak
    | what it never received.
    */

    Route::get('profiles/{profileCode}', [PublicProfileController::class, 'show']);
    Route::get('profiles/{profileCode}/photos', [PublicProfileController::class, 'photos']);
    Route::get('profiles/{profileCode}/contact', [PublicProfileController::class, 'contact']);
    Route::get('profiles/{profileCode}/compatibility', [PublicProfileController::class, 'compatibility']);

    /*
    |----------------------------------------------------------------------
    | Interests
    |----------------------------------------------------------------------
    | The throttle is abuse protection only. Plan quotas are enforced in the
    | action — a Gold member must never be rate-limited into looking like they
    | hit a membership limit.
    */

    Route::get('interests/sent', [InterestController::class, 'sent']);
    Route::get('interests/received', [InterestController::class, 'received']);

    Route::middleware('throttle:interest')->group(function () {
        Route::post('interests', [InterestController::class, 'store']);
        Route::patch('interests/{interest}/accept', [InterestController::class, 'accept']);
        Route::patch('interests/{interest}/decline', [InterestController::class, 'decline']);
        Route::delete('interests/{interest}', [InterestController::class, 'cancel']);
    });

    Route::get('shortlists', [ShortlistController::class, 'index']);
    Route::get('shortlists/me', [ShortlistController::class, 'whoShortlistedMe']);
    Route::post('shortlists', [ShortlistController::class, 'store']);
    // Not an implicit `{user}` binding — `ShortlistController::destroy` accepts
    // either a numeric id or a `profile_code`. See its docblock.
    Route::delete('shortlists/{identifier}', [ShortlistController::class, 'destroy']);

    Route::get('profile-views/received', [ProfileViewController::class, 'received']);
    Route::get('profile-views/sent', [ProfileViewController::class, 'sent']);
    Route::get('profile-views/stats', [ProfileViewController::class, 'stats']);

    Route::get('blocks', [BlockController::class, 'index']);
    Route::post('blocks', [BlockController::class, 'store']);
    Route::delete('blocks/{user}', [BlockController::class, 'destroy']);

    Route::get('reports/categories', [ReportController::class, 'categories']);
    Route::get('reports/mine', [ReportController::class, 'mine']);
    Route::post('reports', [ReportController::class, 'store'])->middleware('throttle:10,60');

    /*
    |----------------------------------------------------------------------
    | Chat
    |----------------------------------------------------------------------
    | /chat/config is what makes the transport swappable: the client asks the
    | server which realtime backend to use and receives matching credentials,
    | so one binary works against Reverb or Firebase.
    */

    Route::get('chat/config', ChatConfigController::class);
    Route::get('chat/quick-replies', ChatQuickRepliesController::class);
    Route::get('chat/templates', ChatQuickRepliesController::class);

    // `ReverbTransport::clientConfig()` has always promised this exact URL as
    // `auth_endpoint`, and `routes/channels.php` has had the three channel
    // authorisation callbacks since Phase 5 — but nothing ever called
    // `Broadcast::routes()`, so the endpoint they depend on 404'd. Every
    // private/presence channel subscription would have failed auth from the
    // first client that tried. `middleware: []` (not the `web` default
    // `Broadcast::routes()` assumes) because this route sits inside the
    // `auth:sanctum` group above — the same bearer token every other member
    // endpoint uses, not a cookie session this SPA never establishes.
    Broadcast::routes(['middleware' => []]);

    Route::get('conversations', [ConversationController::class, 'index']);
    Route::post('conversations', [ConversationController::class, 'store']);
    Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
    Route::patch('conversations/{conversation}/read', [ConversationController::class, 'markRead']);
    Route::patch('conversations/{conversation}/archive', [ConversationController::class, 'archive']);

    Route::get('conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'store'])
        ->middleware('throttle:message');
    Route::post('conversations/{conversation}/typing', [MessageController::class, 'typing'])
        ->middleware('throttle:30,1');

    /*
    |----------------------------------------------------------------------
    | Membership & payments
    |----------------------------------------------------------------------
    | The client never decides a payment succeeded. /payments/verify checks the
    | signature server-side, and the webhook independently confirms.
    */

    Route::get('membership/current', [MembershipController::class, 'current']);
    Route::get('membership/usage', [MembershipController::class, 'usage']);
    Route::get('subscriptions', [MembershipController::class, 'subscriptions']);

    Route::post('payments/order', [PaymentController::class, 'createOrder']);
    Route::post('payments/verify', [PaymentController::class, 'verify']);
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{payment}/invoice', [PaymentController::class, 'invoice']);

    /*
    |----------------------------------------------------------------------
    | Notifications
    |----------------------------------------------------------------------
    */

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('notifications/read-all', [NotificationController::class, 'readAll']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'read']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::get('notifications/settings', [NotificationController::class, 'settings']);
    Route::put('notifications/settings', [NotificationController::class, 'updateSettings']);

    /*
    |----------------------------------------------------------------------
    | Couples' games
    |----------------------------------------------------------------------
    | Four live games (Sync, Find It, Flip, Memory Snap) behind one set of room
    | routes — `{type}` picks the driver, see App\Services\Games. People are
    | addressed by profile_code like everywhere else; a room by its code.
    | Clients poll `GET rooms/{type}/{code}` (cheaply: pass ?since_version=).
    */

    Route::prefix('games')->middleware('throttle:games')->group(function () {
        Route::get('/', [HubController::class, 'catalog']);
        Route::get('scores', [HubController::class, 'scores']);

        Route::get('partners/{profileCode}', [HubController::class, 'partner']);
        Route::get('partners/{profileCode}/vibe', [HubController::class, 'vibe']);

        Route::post('partners/{profileCode}/invites', [GameInviteController::class, 'send'])
            ->middleware('throttle:30,1');
        Route::get('invites/pending', [GameInviteController::class, 'pending']);
        Route::get('invites/{invite}', [GameInviteController::class, 'show']);
        Route::post('invites/{invite}/accept', [GameInviteController::class, 'accept']);
        Route::post('invites/{invite}/decline', [GameInviteController::class, 'decline']);
        Route::post('invites/{invite}/remind', [GameInviteController::class, 'remind'])
            ->middleware('throttle:10,1');

        // Sync played on your own, one person at a time.
        Route::get('partners/{profileCode}/sync', [SyncController::class, 'questions']);
        Route::post('partners/{profileCode}/sync', [SyncController::class, 'submit']);

        Route::post('partners/{profileCode}/solo/memory/start', [SoloController::class, 'startMemory']);
        Route::post('partners/{profileCode}/solo/memory/finish', [SoloController::class, 'finishMemory']);
        Route::post('partners/{profileCode}/solo/spot/start', [SoloController::class, 'startSpot']);
        Route::post('partners/{profileCode}/solo/spot/finish', [SoloController::class, 'finishSpot']);

        // Rooms. Flip may be created open (share-by-link) without a partner.
        Route::post('rooms/flip', [FlipController::class, 'create'])->middleware('throttle:30,1');

        Route::prefix('rooms/{type}/{code}')->where(['type' => 'sync|find_it|flip|memory_snap'])->group(function () {
            Route::get('/', [RoomController::class, 'show']);
            Route::post('join', [RoomController::class, 'join']);
            Route::post('ready', [RoomController::class, 'ready']);
            Route::post('leave', [RoomController::class, 'leave']);
            Route::post('reset', [RoomController::class, 'reset']);
            Route::post('messages', [RoomController::class, 'message'])->middleware('throttle:message');
            Route::post('messages/read', [RoomController::class, 'markRead']);
        });

        Route::post('rooms/sync/{code}/answer', [SyncController::class, 'answer']);
        Route::post('rooms/sync/{code}/next', [SyncController::class, 'next']);
        Route::post('rooms/find_it/{code}/start', [FindItController::class, 'start']);
        Route::post('rooms/find_it/{code}/claim', [FindItController::class, 'claim']);
        Route::post('rooms/flip/{code}/flip', [FlipController::class, 'tile']);
        Route::post('rooms/flip/{code}/preview', [FlipController::class, 'preview']);
        Route::post('rooms/memory_snap/{code}/answer', [MemorySnapController::class, 'answer']);

        // Spot the Difference 1v1: a race over a picture pair, against a member.
        Route::prefix('spot')->group(function () {
            Route::get('puzzles', [SpotController::class, 'puzzles']);
            Route::get('leaderboard', [SpotController::class, 'leaderboard']);
            Route::get('matches', [SpotController::class, 'index']);
            Route::post('matches', [SpotController::class, 'store'])->middleware('throttle:30,1');
            Route::get('matches/{match}', [SpotController::class, 'show']);
            Route::post('matches/{match}/ready', [SpotController::class, 'ready']);
            Route::post('matches/{match}/claim', [SpotController::class, 'claim'])->middleware('throttle:120,1');
            Route::post('matches/{match}/cancel', [SpotController::class, 'cancel']);
            Route::post('matches/{match}/rematch', [SpotController::class, 'rematch'])->middleware('throttle:30,1');
        });

        // The contact/notification routes below were first registered here,
        // inside `games`, so they answered at /api/v1/games/contact/...; kept
        // so nothing that already calls those paths breaks.
        // Part G: Contact details customer approval
        Route::post('contact/request', [\App\Http\Controllers\Api\V1\User\ContactApprovalController::class, 'requestAccess']);
        Route::post('contact/respond', [\App\Http\Controllers\Api\V1\User\ContactApprovalController::class, 'respond']);
        Route::get('contact/requests/pending', [\App\Http\Controllers\Api\V1\User\ContactApprovalController::class, 'pendingRequests']);
        Route::get('contact/requests/approved', [\App\Http\Controllers\Api\V1\User\ContactApprovalController::class, 'approvedRequests']);

        // Part H: Notification sound and channel configuration
        Route::get('notifications/config', [\App\Http\Controllers\Api\V1\User\NotificationConfigController::class, 'show']);
    });

    // /api/v1/contact/... and /api/v1/notifications/config — where the app calls them.
    // Part G: Contact details customer approval
    Route::post('contact/request', [\App\Http\Controllers\Api\V1\User\ContactApprovalController::class, 'requestAccess']);
    Route::post('contact/respond', [\App\Http\Controllers\Api\V1\User\ContactApprovalController::class, 'respond']);
    Route::get('contact/requests/pending', [\App\Http\Controllers\Api\V1\User\ContactApprovalController::class, 'pendingRequests']);
    Route::get('contact/requests/approved', [\App\Http\Controllers\Api\V1\User\ContactApprovalController::class, 'approvedRequests']);

    // Part H: Notification sound and channel configuration
    Route::get('notifications/config', [\App\Http\Controllers\Api\V1\User\NotificationConfigController::class, 'show']);
});
