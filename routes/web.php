<?php

use App\Http\Controllers\Web\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Web\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Web\Admin\ChatModerationController as AdminChatModerationController;
use App\Http\Controllers\Web\Admin\CmsController as AdminCmsController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\LocationController as AdminLocationController;
use App\Http\Controllers\Web\Admin\MasterDataController as AdminMasterDataController;
use App\Http\Controllers\Web\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Web\Admin\MembershipPlanController as AdminMembershipPlanController;
use App\Http\Controllers\Web\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Web\Admin\PhotoModerationController as AdminPhotoModerationController;
use App\Http\Controllers\Web\Admin\ProfileModerationController as AdminProfileModerationController;
use App\Http\Controllers\Web\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Web\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Web\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Web\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Web\Admin\UserController as AdminUserController;
use App\Http\Controllers\Web\Admin\VerificationController as AdminVerificationController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\OtpController;
use App\Http\Controllers\Web\Auth\PasswordResetController;
use App\Http\Controllers\Web\Auth\RegisterController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\InterestController;
use App\Http\Controllers\Web\MatchController;
use App\Http\Controllers\Web\MembershipController;
use App\Http\Controllers\Web\MessageController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\PreviewSearchController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\PublicProfileController;
use App\Http\Controllers\Web\SearchController;
use App\Http\Controllers\Web\ShortlistController;
use App\Http\Controllers\Web\GameController;
use App\Http\Controllers\Web\MemorySnapGameController;
use App\Http\Controllers\Web\FlipGameController;
use App\Http\Controllers\Web\Admin\FlipTileImageController as AdminFlipTileImageController;
use App\Http\Controllers\Web\Admin\KoodalGameAdminController;
use App\Http\Controllers\Web\Admin\MemorySnapAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Website (PHP/Blade)
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// Public — indexable, works signed-out
Route::get('plans', [MembershipController::class, 'plans'])->name('membership.plans');

Route::get('browse', [PreviewSearchController::class, 'index'])
    ->middleware('throttle:preview-search')
    ->name('browse');

Route::get('faq', [PageController::class, 'faqs'])->name('page.faq');
Route::get('success-stories', [PageController::class, 'successStories'])->name('page.stories');
Route::get('contact', [PageController::class, 'contact'])->name('page.contact');
Route::post('contact', [PageController::class, 'storeContact'])
    ->middleware('throttle:contact')
    ->name('page.contact.store');

Route::get('csrf-keepalive', fn () => response()->json(['csrf_token' => csrf_token()]))
    ->name('csrf.keepalive');

/*
|--------------------------------------------------------------------------
| Flip the Tiles Game (1v1 Memory Match)
|--------------------------------------------------------------------------
*/
Route::prefix('flip')->name('flip.')->group(function () {
    Route::get('/', [FlipGameController::class, 'index'])->name('index');
    Route::post('create', [FlipGameController::class, 'create'])->middleware('throttle:30,1')->name('create');
    Route::get('join/{code}', [FlipGameController::class, 'showJoin'])->name('join');
    Route::post('join/{code}', [FlipGameController::class, 'join'])->middleware('throttle:30,1')->name('join.store');
    Route::get('{code}', [FlipGameController::class, 'show'])->name('show');
    Route::post('{code}/flip', [FlipGameController::class, 'flip'])->middleware('throttle:60,1')->name('flip');
    Route::post('{code}/preview', [FlipGameController::class, 'preview'])->middleware('throttle:30,1')->name('preview');
    Route::post('{code}/ready', [FlipGameController::class, 'ready'])->middleware('throttle:30,1')->name('ready');
    Route::post('{code}/chat', [FlipGameController::class, 'chat'])->middleware('throttle:60,1')->name('chat');
    Route::get('{code}/state', [FlipGameController::class, 'state'])->name('state');
});

Route::middleware(['guest:web', 'no.cache'])->group(function () {
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::get('password/forgot', [PasswordResetController::class, 'create'])->name('password.forgot');
    Route::get('password/reset', [PasswordResetController::class, 'edit'])->name('password.reset');

    Route::middleware('throttle:auth')->group(function () {
        Route::post('register', [RegisterController::class, 'store']);
        Route::post('login', [LoginController::class, 'store']);

        Route::post('auth/aadhaar/generate-otp', [\App\Http\Controllers\Web\Auth\AadhaarVerificationController::class, 'generateOtp'])
            ->name('auth.aadhaar.generate-otp');
        Route::post('auth/aadhaar/verify-otp', [\App\Http\Controllers\Web\Auth\AadhaarVerificationController::class, 'verifyOtp'])
            ->name('auth.aadhaar.verify-otp');

        Route::post('password/forgot', [PasswordResetController::class, 'sendResetOtp']);
        Route::post('password/reset', [PasswordResetController::class, 'update']);
    });
});

Route::get('otp/verify', [OtpController::class, 'create'])->name('otp.verify');

Route::middleware('throttle:otp')->group(function () {
    Route::post('otp/verify', [OtpController::class, 'store']);
    Route::post('otp/verify-aadhaar', [OtpController::class, 'verifyAadhaar'])->name('otp.verify-aadhaar');
    Route::post('otp/resend', [OtpController::class, 'resend'])->name('otp.resend');
});

Route::match(['get', 'post'], 'logout', [LoginController::class, 'destroy'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Member area
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:web', 'active.web', 'no.cache'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('search', [SearchController::class, 'index'])->name('search');

    Route::prefix('matches')->name('matches.')->group(function () {
        Route::get('/', [MatchController::class, 'recommended'])->name('recommended');
        Route::get('reference', [MatchController::class, 'referenceMatch'])->name('reference');
        Route::get('new', [MatchController::class, 'newProfiles'])->name('new');
        Route::get('recently-active', [MatchController::class, 'recentlyActive'])->name('recently-active');
        Route::get('mutual', [MatchController::class, 'mutual'])->name('mutual');
        Route::get('near-me', [MatchController::class, 'nearMe'])->name('near-me');
        Route::get('preference-match', [MatchController::class, 'preferenceMatch'])->name('preference-match');
        Route::get('caste-match', [MatchController::class, 'casteMatch'])->name('caste-match');
    });

    Route::prefix('interests')->name('interests.')->group(function () {
        Route::get('/', [InterestController::class, 'index'])->name('index');
        Route::get('sent', [InterestController::class, 'sent'])->name('sent');
        Route::get('received', [InterestController::class, 'received'])->name('received');
        Route::post('/', [InterestController::class, 'store'])->name('store');
        Route::post('{interest}/accept', [InterestController::class, 'accept'])->name('accept');
        Route::post('{interest}/decline', [InterestController::class, 'decline'])->name('decline');
        Route::post('{interest}/cancel', [InterestController::class, 'cancel'])->name('cancel');
    });

    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::post('/', [MessageController::class, 'start'])->name('start');
        Route::get('{conversation}', [MessageController::class, 'show'])->name('show');
        Route::post('{conversation}/messages', [MessageController::class, 'store'])->name('store');
        Route::post('{conversation}/archive', [MessageController::class, 'archive'])->name('archive');
    });

    Route::get('membership', [MembershipController::class, 'index'])->name('membership.index');

    Route::post('payments/order', [PaymentController::class, 'createOrder'])->name('payments.order');
    Route::post('payments/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::get('payments/{payment}/invoice', [PaymentController::class, 'invoice'])->name('payments.invoice');

    Route::prefix('shortlist')->name('shortlist.')->group(function () {
        Route::get('/', [ShortlistController::class, 'index'])->name('index');
        Route::get('received', [ShortlistController::class, 'whoShortlistedMe'])->name('received');
        Route::post('/', [ShortlistController::class, 'store'])->name('store');
        Route::delete('{identifier}', [ShortlistController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('profile-views')->name('profile-views.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\ProfileViewController::class, 'index'])->name('index');
        Route::get('sent', [\App\Http\Controllers\Web\ProfileViewController::class, 'sent'])->name('sent');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');

        Route::prefix('complete')->name('wizard.')->group(function () {
            Route::get('/', [ProfileController::class, 'wizardStart'])->name('start');
            Route::get('{step}', [ProfileController::class, 'wizardStep'])
                ->whereIn('step', ['about-you', 'religion-location', 'education-career', 'family-lifestyle', 'photos', 'jadhagam', 'aadhaar', 'preferences'])
                ->name('step');

            Route::post('about-you', [ProfileController::class, 'updateAboutYou'])->name('about-you');
            Route::post('religion-location', [ProfileController::class, 'updateReligionLocation'])->name('religion-location');
            Route::post('education-career', [ProfileController::class, 'updateEducationCareer'])->name('education-career');
            Route::post('family-lifestyle', [ProfileController::class, 'updateFamilyLifestyle'])->name('family-lifestyle');
            Route::post('preferences', [ProfileController::class, 'updatePreferences'])->name('preferences');

            Route::post('aadhaar/generate-otp', [ProfileController::class, 'generateAadhaarOtp'])->name('aadhaar.generate-otp');
            Route::post('aadhaar/verify-otp', [ProfileController::class, 'verifyAadhaarOtp'])->name('aadhaar.verify-otp');
        });

        Route::post('photos', [ProfileController::class, 'uploadPhoto'])->name('photos.store');
        Route::patch('photos/{photo}/primary', [ProfileController::class, 'setPrimaryPhoto'])->name('photos.primary');
        Route::delete('photos/{photo}', [ProfileController::class, 'destroyPhoto'])->name('photos.destroy');

        Route::post('jadhagam', [ProfileController::class, 'uploadJadhagam'])->name('jadhagam.store');
        Route::delete('jadhagam/{jadhagam}', [ProfileController::class, 'destroyJadhagam'])->name('jadhagam.destroy');

        Route::get('{slug}/rasi-porutham', [PublicProfileController::class, 'rasiPorutham'])->name('rasi');
        Route::get('{slug}/preference', [PublicProfileController::class, 'preference'])->name('preference');
        Route::get('{slug}', [PublicProfileController::class, 'show'])->name('public');
        Route::post('{slug}/contact', [PublicProfileController::class, 'contact'])->name('public.contact');
        Route::post('{slug}/contact/request', [PublicProfileController::class, 'requestContact'])->name('public.contact.request');
    });

    // Contact Requests Management for Members
    Route::prefix('contact-requests')->name('contact-requests.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\ContactApprovalWebController::class, 'index'])->name('index');
        Route::post('{approval}/approve', [\App\Http\Controllers\Web\ContactApprovalWebController::class, 'approve'])->name('approve');
        Route::post('{approval}/reject', [\App\Http\Controllers\Web\ContactApprovalWebController::class, 'reject'])->name('reject');
    });

    // Presence Routes
    Route::post('presence/ping', [\App\Http\Controllers\Web\PresenceController::class, 'ping'])->name('presence.ping');
    Route::get('presence/{user}/status', [\App\Http\Controllers\Web\PresenceController::class, 'status'])->name('presence.status');

    // Memory Snap Routes
    Route::prefix('memory-snap')->name('games.memory_snap.')->group(function () {
        Route::get('/start/{partner?}', [MemorySnapGameController::class, 'index'])->name('start');
        Route::get('/room/{code}', [MemorySnapGameController::class, 'showRoom'])->name('room');
        Route::get('/room/{code}/state', [MemorySnapGameController::class, 'state'])->name('state');
        Route::post('/room/{code}/join', [MemorySnapGameController::class, 'join'])->name('join');
        Route::post('/room/{code}/player-join', [MemorySnapGameController::class, 'playerJoin'])->name('player_join');
        Route::post('/room/{code}/ready', [MemorySnapGameController::class, 'toggleReady'])->name('ready');
        Route::post('/room/{code}/answer', [MemorySnapGameController::class, 'submitAnswer'])->name('answer');
        Route::post('/room/{code}/chat', [MemorySnapGameController::class, 'sendChat'])->name('chat');
        Route::post('/room/{code}/reset', [MemorySnapGameController::class, 'resetGame'])->name('reset');
        Route::post('/room/{code}/next-game', [MemorySnapGameController::class, 'setNextGame'])->name('next_game');
    });

    // Live Game Room Routes
    Route::get('games/room/{room_code}', [\App\Http\Controllers\Web\LiveGameRoomController::class, 'show'])->name('games.room');
    Route::get('games/room/{room_code}/poll', [\App\Http\Controllers\Web\LiveGameRoomController::class, 'pollState'])->name('games.room.poll');
    Route::post('games/room/{room_code}/ready', [\App\Http\Controllers\Web\LiveGameRoomController::class, 'toggleReady'])->name('games.room.ready');
    Route::post('games/room/{room_code}/answer', [\App\Http\Controllers\Web\LiveGameRoomController::class, 'submitAnswer'])->name('games.room.answer');
    Route::post('games/room/{room_code}/next-question', [\App\Http\Controllers\Web\LiveGameRoomController::class, 'nextQuestion'])->name('games.room.next');
    Route::post('games/room/{room_code}/claim-find-it', [\App\Http\Controllers\Web\LiveGameRoomController::class, 'claimFindItDifference'])->name('games.room.claim_find_it');
    Route::post('games/room/{room_code}/start', [\App\Http\Controllers\Web\LiveGameRoomController::class, 'startGameRoom'])->name('games.room.start');
    Route::post('games/room/{room_code}/reset', [\App\Http\Controllers\Web\LiveGameRoomController::class, 'resetGameRoom'])->name('games.room.reset');
    Route::post('games/room/{room_code}/leave', [\App\Http\Controllers\Web\LiveGameRoomController::class, 'leave'])->name('games.room.leave');

    // Room Chat Routes
    Route::post('games/room/{room_code}/messages', [\App\Http\Controllers\Web\GameChatController::class, 'send'])->name('games.chat.send');
    Route::post('games/room/{room_code}/messages/read', [\App\Http\Controllers\Web\GameChatController::class, 'markRead'])->name('games.chat.read');

    // Invites Routes
    Route::get('games/invites/pending', [\App\Http\Controllers\Web\GameInviteController::class, 'pending'])->name('games.invites.pending');
    Route::match(['get', 'post'], 'games/invites/{invite}/accept', [\App\Http\Controllers\Web\GameInviteController::class, 'accept'])->name('games.invites.accept');
    Route::post('games/invites/{invite}/decline', [\App\Http\Controllers\Web\GameInviteController::class, 'decline'])->name('games.invites.decline');
    Route::post('games/invites/{invite}/remind', [\App\Http\Controllers\Web\GameInviteController::class, 'remind'])->name('games.invites.remind');

    // Spot the Difference 1v1 Routes
    Route::get('spot-matches/invitation/{token}', [\App\Http\Controllers\Web\InvitationController::class, 'show'])->name('spot.invitation.accept');

    Route::prefix('spot-matches')->name('spot.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\SpotMatchController::class, 'index'])->name('matches.index');
        Route::get('leaderboard', [\App\Http\Controllers\Web\SpotMatchController::class, 'leaderboard'])->name('leaderboard');
        Route::get('create', [\App\Http\Controllers\Web\SpotMatchController::class, 'create'])->name('matches.create');
        Route::post('/', [\App\Http\Controllers\Web\SpotMatchController::class, 'store'])->name('matches.store');
        Route::get('{id}', [\App\Http\Controllers\Web\SpotMatchController::class, 'show'])->name('matches.show');
        Route::get('{id}/state', [\App\Http\Controllers\Web\SpotMatchController::class, 'state'])->name('matches.state');
        Route::post('{id}/ready', [\App\Http\Controllers\Web\SpotMatchController::class, 'ready'])->name('matches.ready');
        Route::post('{id}/claim', [\App\Http\Controllers\Web\SpotMatchController::class, 'claim'])->middleware('throttle:60,1')->name('matches.claim');
        Route::post('{id}/cancel', [\App\Http\Controllers\Web\SpotMatchController::class, 'cancel'])->name('matches.cancel');
        Route::post('{id}/rematch', [\App\Http\Controllers\Web\SpotMatchController::class, 'rematch'])->name('matches.rematch');
    });

    Route::prefix('games/{partner}')->name('games.')->whereNumber('partner')->group(function () {
        Route::get('/', [GameController::class, 'hub'])->name('hub');
        Route::post('invite', [\App\Http\Controllers\Web\GameInviteController::class, 'send'])->name('invite');
        Route::get('sync', [GameController::class, 'sync'])->name('sync');
        Route::post('sync', [GameController::class, 'postSync'])->name('postSync');
        Route::get('find-it', [GameController::class, 'findIt'])->name('find_it');
        Route::post('find-it', [GameController::class, 'postFindIt'])->name('postFindIt');
        Route::get('memory-snap', fn ($partner) => redirect()->route('games.memory_snap.start', $partner))->name('memory_snap');
        Route::get('memory', [GameController::class, 'memory'])->name('memory');
        Route::post('memory', [GameController::class, 'postMemory'])->name('postMemory');
        Route::get('spot', [GameController::class, 'spot'])->name('spot');
        Route::post('spot', [GameController::class, 'postSpot'])->name('postSpot');
        Route::get('rasi', [GameController::class, 'rasiPorutham'])->name('rasi');
        Route::get('preference', [GameController::class, 'preference'])->name('preference');
        Route::get('vibe', [GameController::class, 'vibe'])->name('vibe');
    });
});

/*
|--------------------------------------------------------------------------
| Admin panel (PHP/Blade)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware(['no.cache'])->group(function () {
        Route::get('login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])->middleware('throttle:auth');
    });

    Route::match(['get', 'post'], 'logout', [AdminLoginController::class, 'destroy'])
        ->name('logout');

    Route::middleware(['auth:web', 'active.web', 'no.cache'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::post('export', [AdminUserController::class, 'export'])->name('export');
            Route::get('{user}', [AdminUserController::class, 'show'])->name('show');
            Route::patch('{user}/{action}', [AdminUserController::class, 'action'])
                ->where('action', 'suspend|activate|block|unblock|verify')
                ->name('action');
            Route::delete('{user}', [AdminUserController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('profiles')->name('profiles.')->group(function () {
            Route::get('/', [AdminProfileModerationController::class, 'index'])->name('index');
            Route::get('{profile}', [AdminProfileModerationController::class, 'show'])->name('show');
            Route::post('{profile}/approve', [AdminProfileModerationController::class, 'approve'])->name('approve');
            Route::post('{profile}/reject', [AdminProfileModerationController::class, 'reject'])->name('reject');
        });

        Route::prefix('photos')->name('photos.')->group(function () {
            Route::get('/', [AdminPhotoModerationController::class, 'index'])->name('index');
            Route::post('bulk', [AdminPhotoModerationController::class, 'bulk'])->name('bulk');
            Route::post('{photo}/approve', [AdminPhotoModerationController::class, 'approve'])->name('approve');
            Route::post('{photo}/reject', [AdminPhotoModerationController::class, 'reject'])->name('reject');
        });

        Route::prefix('jadhagam')->name('jadhagam.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Web\Admin\JadhagamModerationController::class, 'index'])->name('index');
            Route::post('{jadhagam}/approve', [\App\Http\Controllers\Web\Admin\JadhagamModerationController::class, 'approve'])->name('approve');
            Route::post('{jadhagam}/reject', [\App\Http\Controllers\Web\Admin\JadhagamModerationController::class, 'reject'])->name('reject');
        });

        Route::prefix('verification')->name('verification.')->group(function () {
            Route::get('/', [AdminVerificationController::class, 'index'])->name('index');
            Route::get('{document}', [AdminVerificationController::class, 'show'])->name('show');
            Route::patch('{document}/{action}', [AdminVerificationController::class, 'action'])
                ->where('action', 'approve|reject|resubmit')
                ->name('action');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [AdminReportController::class, 'index'])->name('index');
            Route::get('{report}', [AdminReportController::class, 'show'])->name('show');
            Route::get('{report}/evidence/{index}', [AdminReportController::class, 'evidence'])
                ->where('index', '[0-9]+')
                ->name('evidence');
            Route::post('{report}/assign', [AdminReportController::class, 'assign'])->name('assign');
            Route::post('{report}/resolve', [AdminReportController::class, 'resolve'])->name('resolve');
            Route::post('{report}/dismiss', [AdminReportController::class, 'dismiss'])->name('dismiss');
        });

        Route::prefix('plans')->name('plans.')->group(function () {
            Route::get('/', [AdminMembershipPlanController::class, 'index'])->name('index');
            Route::get('create', [AdminMembershipPlanController::class, 'create'])->name('create');
            Route::post('/', [AdminMembershipPlanController::class, 'store'])->name('store');
            Route::get('{membership_plan}/edit', [AdminMembershipPlanController::class, 'edit'])->name('edit');
            Route::put('{membership_plan}', [AdminMembershipPlanController::class, 'update'])->name('update');
            Route::delete('{membership_plan}', [AdminMembershipPlanController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('/', [AdminSubscriptionController::class, 'index'])->name('index');
            Route::get('{subscription}', [AdminSubscriptionController::class, 'show'])->name('show');
            Route::post('{subscription}/extend', [AdminSubscriptionController::class, 'extend'])->name('extend');
            Route::post('{subscription}/cancel', [AdminSubscriptionController::class, 'cancel'])->name('cancel');
        });

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [AdminPaymentController::class, 'index'])->name('index');
            Route::post('export', [AdminPaymentController::class, 'export'])->name('export');
            Route::get('{payment}', [AdminPaymentController::class, 'show'])->name('show');
            Route::post('{payment}/refund', [AdminPaymentController::class, 'refund'])->name('refund');
        });

        Route::prefix('chat')->name('chat.')->group(function () {
            Route::get('/', [AdminChatModerationController::class, 'index'])->name('index');
            Route::get('{conversation}', [AdminChatModerationController::class, 'show'])->name('show');
        });

        Route::prefix('cms')->name('cms.')->group(function () {
            Route::get('broadcast', [AdminCmsController::class, 'broadcastForm'])->name('broadcast.form');
            Route::post('broadcast', [AdminCmsController::class, 'broadcast'])->name('broadcast');

            foreach (['pages', 'faqs', 'banners', 'success-stories'] as $cmsType) {
                Route::prefix($cmsType)->name($cmsType.'.')->group(function () {
                    Route::get('/', [AdminCmsController::class, 'index'])->name('index');
                    Route::get('create', [AdminCmsController::class, 'create'])->name('create');
                    Route::post('/', [AdminCmsController::class, 'store'])->name('store');
                    Route::get('{id}/edit', [AdminCmsController::class, 'edit'])->where('id', '[0-9]+')->name('edit');
                    Route::put('{id}', [AdminCmsController::class, 'update'])->where('id', '[0-9]+')->name('update');
                    Route::delete('{id}', [AdminCmsController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
                });
            }
        });

        // Game content (questions, pictures, tiles, stories) is site content.
        // These controllers check no permission of their own, and the group
        // around them only requires a login — so without this any member
        // could open them and add, edit or delete game content.
        Route::middleware('can:cms.manage')->group(function () {
            Route::prefix('puzzles')->name('puzzles.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Web\Admin\PuzzleAdminController::class, 'index'])->name('index');
                Route::get('create', [\App\Http\Controllers\Web\Admin\PuzzleAdminController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Web\Admin\PuzzleAdminController::class, 'store'])->name('store');
                Route::get('{id}/edit', [\App\Http\Controllers\Web\Admin\PuzzleAdminController::class, 'edit'])->name('edit');
                Route::put('{id}', [\App\Http\Controllers\Web\Admin\PuzzleAdminController::class, 'update'])->name('update');
                Route::delete('{id}', [\App\Http\Controllers\Web\Admin\PuzzleAdminController::class, 'destroy'])->name('destroy');
                Route::post('compare', [\App\Http\Controllers\Web\Admin\PuzzleAdminController::class, 'compareImages'])->name('compare');
            });

            Route::prefix('flip-tiles')->name('flip-tiles.')->group(function () {
                Route::get('/', [AdminFlipTileImageController::class, 'index'])->name('index');
                Route::post('upload', [AdminFlipTileImageController::class, 'upload'])->name('upload');
                Route::patch('{image}/toggle', [AdminFlipTileImageController::class, 'toggle'])->name('toggle');
                Route::delete('{image}', [AdminFlipTileImageController::class, 'destroy'])->name('destroy');
                Route::post('settings', [AdminFlipTileImageController::class, 'updateSettings'])->name('settings');
            });

            Route::prefix('games')->name('games.')->group(function () {
                Route::prefix('questions')->name('questions.')->group(function () {
                    Route::get('/', [KoodalGameAdminController::class, 'questions'])->name('index');
                    Route::get('create', [KoodalGameAdminController::class, 'createQuestion'])->name('create');
                    Route::post('/', [KoodalGameAdminController::class, 'storeQuestion'])->name('store');
                    Route::get('{id}/edit', [KoodalGameAdminController::class, 'editQuestion'])->name('edit');
                    Route::put('{id}', [KoodalGameAdminController::class, 'updateQuestion'])->name('update');
                    Route::delete('{id}', [KoodalGameAdminController::class, 'destroyQuestion'])->name('destroy');
                });

                Route::prefix('find-it')->name('find_it.')->group(function () {
                    Route::get('/', [KoodalGameAdminController::class, 'findItChallenges'])->name('index');
                    Route::get('create', [KoodalGameAdminController::class, 'createFindIt'])->name('create');
                    Route::post('/', [KoodalGameAdminController::class, 'storeFindIt'])->name('store');
                    Route::get('{id}/edit', [KoodalGameAdminController::class, 'editFindIt'])->name('edit');
                    Route::put('{id}', [KoodalGameAdminController::class, 'updateFindIt'])->name('update');
                    Route::delete('{id}', [KoodalGameAdminController::class, 'destroyFindIt'])->name('destroy');
                });

                Route::prefix('memory-snap')->name('memory_snap.')->group(function () {
                    Route::get('/', [MemorySnapAdminController::class, 'index'])->name('index');
                    Route::get('create', [MemorySnapAdminController::class, 'create'])->name('create');
                    Route::post('/', [MemorySnapAdminController::class, 'store'])->name('store');
                    Route::get('{id}/edit', [MemorySnapAdminController::class, 'edit'])->name('edit');
                    Route::get('{id}', [MemorySnapAdminController::class, 'show'])->name('show');
                    Route::put('{id}', [MemorySnapAdminController::class, 'update'])->name('update');
                    Route::delete('{id}', [MemorySnapAdminController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('vibe-stories')->name('vibe_stories.')->group(function () {
                    Route::get('/', [KoodalGameAdminController::class, 'vibeStories'])->name('index');
                    Route::get('create', [KoodalGameAdminController::class, 'createVibeStory'])->name('create');
                    Route::post('/', [KoodalGameAdminController::class, 'storeVibeStory'])->name('store');
                    Route::get('{id}/edit', [KoodalGameAdminController::class, 'editVibeStory'])->name('edit');
                    Route::put('{id}', [KoodalGameAdminController::class, 'updateVibeStory'])->name('update');
                    Route::post('{id}/copy', [KoodalGameAdminController::class, 'copyVibeStory'])->name('copy');
                    Route::delete('{id}', [KoodalGameAdminController::class, 'destroyVibeStory'])->name('destroy');
                });
            });
        });

        Route::prefix('media')->name('media.')->group(function () {
            Route::get('/', [AdminMediaController::class, 'index'])->name('index');
            Route::post('/', [AdminMediaController::class, 'store'])->name('store');
            Route::delete('{upload}', [AdminMediaController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('master-data')->name('master-data.')->group(function () {
            Route::get('{type}', [AdminMasterDataController::class, 'index'])->name('index');
            Route::post('{type}', [AdminMasterDataController::class, 'store'])->name('store');
            Route::put('{type}/{id}', [AdminMasterDataController::class, 'update'])->name('update');
            Route::delete('{type}/{id}', [AdminMasterDataController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('locations')->name('locations.')->group(function () {
            Route::post('import', [AdminLocationController::class, 'import'])->name('import');
            Route::get('{type}', [AdminLocationController::class, 'index'])->name('index');
            Route::post('{type}', [AdminLocationController::class, 'store'])->name('store');
            Route::put('{type}/{id}', [AdminLocationController::class, 'update'])->name('update');
            Route::delete('{type}/{id}', [AdminLocationController::class, 'destroy'])->name('destroy');
        });

        Route::middleware('role:super_admin')->group(function () {
            Route::prefix('staff')->name('staff.')->group(function () {
                Route::get('/', [AdminStaffController::class, 'index'])->name('index');
                Route::get('create', [AdminStaffController::class, 'create'])->name('create');
                Route::get('roles', [AdminStaffController::class, 'roles'])->name('roles');
                Route::put('roles/{role}', [AdminStaffController::class, 'updateRole'])->name('roles.update');
                Route::post('/', [AdminStaffController::class, 'store'])->name('store');
                Route::get('{staff}/edit', [AdminStaffController::class, 'edit'])->name('edit');
                Route::put('{staff}', [AdminStaffController::class, 'update'])->name('update');
                Route::delete('{staff}', [AdminStaffController::class, 'destroy'])->name('destroy');
            });
        });

        Route::get('settings', [AdminSettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [AdminSettingsController::class, 'update'])->name('settings.update');
        Route::get('settings/firebase', [\App\Http\Controllers\Web\Admin\FirebaseSettingsController::class, 'index'])->name('firebase.index');
        Route::put('settings/firebase', [\App\Http\Controllers\Web\Admin\FirebaseSettingsController::class, 'update'])->name('firebase.update');
        Route::post('settings/firebase/test', [\App\Http\Controllers\Web\Admin\FirebaseSettingsController::class, 'sendTest'])->name('firebase.test');
        Route::get('notification-logs', [\App\Http\Controllers\Web\Admin\NotificationLogWebController::class, 'index'])->name('notification-logs.index');
        Route::get('audit', AdminAuditLogController::class)->name('audit');
        Route::get('upgrade', [\App\Http\Controllers\Web\Admin\UpgradeController::class, 'index'])->name('upgrade.index');
        Route::post('upgrade', [\App\Http\Controllers\Web\Admin\UpgradeController::class, 'update'])->name('upgrade.store');
    });
});

Route::get('{slug}', [PageController::class, 'show'])
    ->where('slug', '(?!up$)[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('page.show');
