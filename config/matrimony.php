<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Profile
    |---------------------------------------------------------------------------
    | Completion weights decide what "80% complete" means. Product retunes these
    | regularly, so they live here rather than in ProfileCompletion — changing
    | the definition of a complete profile must never need a migration or a
    | mobile release.
    */

    'profile' => [
        'completion_weights' => [
            'basic' => 15,
            'photos' => 15,
            'religious' => 10,
            'education' => 10,
            'professional' => 10,
            'location' => 10,
            'family' => 10,
            'lifestyle' => 5,
            'about' => 5,
            'aadhaar' => 10,
        ],

        // Legal minimum marriage age in India, applied at registration.
        'min_age' => ['male' => 21, 'female' => 18],
        'max_age' => 75,

        // Fallback when the plan carries no photo_limit.
        'photo_limit_default' => 5,

        // Below this, the app nags the user to finish before browsing.
        'min_completion_to_browse' => 40,

        'approval_required' => env('PROFILE_APPROVAL_REQUIRED', true),
        'photo_moderation_required' => env('PHOTO_MODERATION_REQUIRED', true),
    ],

    /*
    |---------------------------------------------------------------------------
    | Astrology
    |---------------------------------------------------------------------------
    | The 12 Raasis (moon signs) and 27 Nakshatras (birth stars) are a fixed
    | system, not admin-editable master data — one shared list here rather
    | than duplicated across the profile wizard and the matches filter.
    | UserProfile::raasi / ::star stay plain strings, same as caste/gothra.
    */

    'astrology' => [
        'raasis' => [
            'Mesha (Aries)', 'Vrishabha (Taurus)', 'Mithuna (Gemini)', 'Karkataka (Cancer)',
            'Simha (Leo)', 'Kanya (Virgo)', 'Tula (Libra)', 'Vrischika (Scorpio)',
            'Dhanus (Sagittarius)', 'Makara (Capricorn)', 'Kumbha (Aquarius)', 'Meena (Pisces)',
        ],

        'nakshatras' => [
            'Ashwini', 'Bharani', 'Krittika', 'Rohini', 'Mrigashira', 'Ardra', 'Punarvasu',
            'Pushya', 'Ashlesha', 'Magha', 'Purva Phalguni', 'Uttara Phalguni', 'Hasta',
            'Chitra', 'Swati', 'Vishakha', 'Anuradha', 'Jyeshtha', 'Mula', 'Purva Ashadha',
            'Uttara Ashadha', 'Shravana', 'Dhanishtha', 'Shatabhisha', 'Purva Bhadrapada',
            'Uttara Bhadrapada', 'Revati',
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Search
    |---------------------------------------------------------------------------
    | max_offset caps deep pagination. Beyond this the API switches to keyset —
    | offset 50,000 is not a user browsing, it is someone enumerating the
    | database.
    */

    'search' => [
        'per_page' => 20,
        'max_per_page' => 50,
        'max_offset' => 500,
        'cache_ttl' => 60,

        'sorts' => [
            'recommended', 'recently_joined', 'recently_active',
            'profile_completeness', 'age_asc', 'age_desc',
        ],

        /*
        | The signed-out preview at `/browse`. A shop window, not a search:
        | one page, no paging, no sort control, and only the handful of
        | filters a first-time visitor actually opens the form for.
        |
        | `count_buckets` is a privacy control, not a display preference. The
        | page states how many members match a filter set, and an exact count
        | over an arbitrary filter combination is an enumeration oracle —
        | narrow the filters far enough and "1" identifies a person. Rounding
        | down to the nearest bucket collapses that to "fewer than 50 / at
        | least 50", which identifies nobody. Anything under the smallest
        | bucket is reported qualitatively, with no number at all. Buckets
        | must stay sorted ascending; RunPreviewSearch walks them in order.
        */
        'preview' => [
            'limit' => 12,
            'cache_ttl' => 300,
            'count_buckets' => [50, 100, 250, 500, 1000, 2500, 5000, 10000, 25000],
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Matching
    |---------------------------------------------------------------------------
    | Weights sum to 100. Each key maps to a Criterion class in
    | App\Services\Matching\Criteria. Adding a criterion is a new class plus a
    | line here — MatchScorer itself never changes.
    */

    'matching' => [
        'weights' => [
            'religion' => 20,
            'community' => 15,
            'age' => 12,
            'mother_tongue' => 10,
            'location' => 10,
            'education' => 10,
            'occupation' => 8,
            'lifestyle' => 6,
            'income' => 5,
            'height' => 4,
        ],

        // Applied after the weighted sum. A perfect match who abandoned the
        // account is not a match.
        'multipliers' => [
            'verified' => 1.10,
            'active_within_7_days' => 1.05,
            'dormant_over_90_days' => 0.80,
            'completeness_min' => 0.85,
            'completeness_max' => 1.05,
        ],

        // Scores are precomputed; this is how many we keep per user.
        'store_top_n' => 200,
        'recompute' => 'nightly',
        'cold_start_pool' => 500,
    ],

    /*
    |---------------------------------------------------------------------------
    | Chat
    |---------------------------------------------------------------------------
    | `transport` picks the RealtimeTransport binding; `requires` is the consent
    | rule enforced by ChatPolicy on every send.
    */

    'chat' => [
        'transport' => env('CHAT_TRANSPORT', 'reverb'),   // reverb | firebase
        'requires' => env('CHAT_REQUIRES', 'mutual_interest'), // mutual_interest | premium | open
        'message_max_length' => 2000,
        'page_size' => 30,
        'presence_ttl' => 60,
        'typing_throttle' => 2,
        // Longer than the throttle on purpose: the client hides the indicator
        // on its own timer, so this must outlive the gap between two frames or
        // the dots flicker while someone is still typing.
        'typing_ttl' => 5,
        'image_url_ttl' => 15,          // minutes
        'quick_replies' => [
            'male' => [
                'Hi! I liked your profile and would like to connect.',
                'Hello! Hope you are having a wonderful day.',
                'Hi, could we connect with each other\'s families?',
                'Hello, are you open to having a conversation?',
                'Hi! Please feel free to view my full profile.',
                'Namaste! Interested in taking this forward.',
            ],
            'female' => [
                'Hi! Thank you for showing interest in my profile.',
                'Hello! I saw your profile and would love to connect.',
                'Hi! Are your parents also managing your profile?',
                'Hello! Hope you are doing well today.',
                'Hi! Feel free to connect with my family as well.',
                'Namaste! Would love to know more about you.',
            ],
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Media
    |---------------------------------------------------------------------------
    | The client compresses before upload as a courtesy to the user's data plan;
    | these limits are the trust boundary and are enforced server-side.
    */

    'media' => [
        'photo' => [
            'max_size_kb' => 5120,
            'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_dimension' => 1920,
            'quality' => 85,
            'thumbnail' => 400,
            'blur_radius' => 30,
        ],
        /*
         | The admin media library (banner art, success-story photos, CMS OG
         | images). Separate from `photo` because these are *not* the same
         | thing: a member photo is resized, thumbnailed and blurred by
         | ProcessProfilePhoto and only ever served through a derivative,
         | while these are stored once and served as-is from the public disk.
         |
         | **SVG is deliberately absent from `mimes`.** An SVG is a document
         | that can carry <script>, browsers execute it when it is served as
         | image/svg+xml at a top-level URL, and these files land on the
         | *public* disk under a URL anyone can open. Adding 'svg' here is
         | stored XSS on the marketing site, not a convenience.
         */
        'upload' => [
            'max_size_kb' => 5120,
            'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
            'collections' => ['general', 'banners', 'success-stories', 'social'],
        ],

        'document' => [
            'max_size_kb' => 10240,
            'mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
            // Short TTL: a verification document link should be useless by the
            // time it reaches anywhere it should not be.
            'signed_url_ttl' => 5,      // minutes
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Interests
    |---------------------------------------------------------------------------
    */

    'interests' => [
        'message_max_length' => 500,
        // Days before a declined interest may be re-sent to the same person.
        'resend_cooldown_days' => 30,
    ],

    /*
    |---------------------------------------------------------------------------
    | Notifications
    |---------------------------------------------------------------------------
    | Types listed here are the allow-list; a type absent from this map cannot
    | be dispatched. `digest` types are never sent individually.
    */

    'notifications' => [
        'types' => [
            'interest_received' => ['route' => '/interests/received', 'channel' => 'interests'],
            'interest_accepted' => ['route' => '/matches/mutual', 'channel' => 'interests'],
            'interest_declined' => ['route' => '/interests/sent', 'channel' => 'interests'],
            'new_message' => ['route' => '/chat', 'channel' => 'messages'],
            // Game invites are time-sensitive (24h) and the sender is waiting on
            // the other side, so they ride the interests channel setting.
            'game_invite' => ['route' => '/games/invites', 'channel' => 'interests'],
            'game_invite_accepted' => ['route' => '/games/invites', 'channel' => 'interests'],
            'game_result' => ['route' => '/games', 'channel' => 'interests'],
            'new_profile' => ['route' => '/matches', 'channel' => 'interests'],
            'preference_match' => ['route' => '/matches', 'channel' => 'interests'],
            'profile_shortlisted' => ['route' => '/shortlists/me', 'channel' => 'interests'],
            'game_accepted' => ['route' => '/games', 'channel' => 'interests'],
            'game_score' => ['route' => '/games', 'channel' => 'interests'],
            'game_chat' => ['route' => '/games', 'channel' => 'messages'],
            'chat_message' => ['route' => '/chat', 'channel' => 'messages'],
            'jadhagam_approved' => ['route' => '/profile', 'channel' => 'moderation'],
            'jadhagam_rejected' => ['route' => '/profile/edit', 'channel' => 'moderation'],
            'contact_request' => ['route' => '/contact/requests', 'channel' => 'interests'],
            'contact_approved' => ['route' => '/contact/approved', 'channel' => 'interests'],
            'contact_rejected' => ['route' => '/contact/requests', 'channel' => 'interests'],
            'profile_viewed' => ['route' => '/profile-views/received', 'channel' => 'interests', 'digest' => true],
            'shortlisted' => ['route' => '/shortlists/me', 'channel' => 'interests', 'digest' => true],
            'membership_purchased' => ['route' => '/membership', 'channel' => 'membership'],
            'membership_expiring' => ['route' => '/membership/plans', 'channel' => 'membership'],
            'profile_approved' => ['route' => '/profile', 'channel' => 'moderation'],
            'profile_rejected' => ['route' => '/profile/edit', 'channel' => 'moderation'],
            'photo_approved' => ['route' => '/profile/photos', 'channel' => 'moderation'],
            'photo_rejected' => ['route' => '/profile/photos', 'channel' => 'moderation'],
            'verification_approved' => ['route' => '/verification', 'channel' => 'moderation'],
            'verification_rejected' => ['route' => '/verification', 'channel' => 'moderation'],
            // Moderation outcomes. Dispatched with force: true — see
            // NotificationDispatcher — because a warning a member has muted is
            // a warning they can truthfully say they never received.
            'account_warning' => ['route' => '/notifications', 'channel' => 'moderation'],
            'account_suspended' => ['route' => '/notifications', 'channel' => 'moderation'],
            // The other half of a suspension. A reinstated member who is never
            // told is a member who does not come back, because from their side
            // nothing distinguishes "lifted" from "still suspended".
            'account_reinstated' => ['route' => '/notifications', 'channel' => 'moderation'],
            'report_resolved' => ['route' => '/reports/mine', 'channel' => 'moderation'],
            'announcement' => ['route' => '/notifications', 'channel' => 'announcements'],
            // Staff-facing. Exports are queued, so the requester needs to be
            // told where the file went; the row carries the download path and
            // the panel signs it on demand.
            'export_ready' => ['route' => '/exports', 'channel' => 'announcements'],
        ],

        'expiry_reminder_days' => [7, 3, 1],
        'digest_hour' => 19,
        'fcm_batch_size' => 500,
    ],

    /*
    |---------------------------------------------------------------------------
    | Membership
    |---------------------------------------------------------------------------
    | The full feature schema. The admin plan editor is generated from this, so
    | a typo cannot create a silently-ignored feature. -1 always means unlimited.
    */

    'membership' => [
        'features' => [
            'interests_per_month' => ['type' => 'quota', 'period' => 'month', 'label' => 'Interests per month'],
            'profile_views_per_day' => ['type' => 'quota', 'period' => 'day', 'label' => 'Profile views per day'],
            'contact_views_per_month' => ['type' => 'quota', 'period' => 'month', 'label' => 'Contact views per month'],
            'photo_limit' => ['type' => 'quota', 'period' => 'none', 'label' => 'Photo uploads'],
            'can_chat' => ['type' => 'capability', 'label' => 'Chat access'],
            'can_view_contact' => ['type' => 'capability', 'label' => 'Contact access'],
            'can_see_profile_viewers' => ['type' => 'capability', 'label' => 'See who viewed you'],
            'advanced_search' => ['type' => 'capability', 'label' => 'Advanced search'],
            'featured_profile' => ['type' => 'capability', 'label' => 'Featured profile'],
            'priority_matching' => ['type' => 'capability', 'label' => 'Priority matching'],
            'support_priority' => ['type' => 'enum', 'options' => ['standard', 'priority'], 'label' => 'Support priority'],
        ],

        // Additive: remaining days carry over into the new term. The
        // alternative (prorated refund) means a gateway refund per upgrade and
        // accounting noise for no user benefit.
        'upgrade_strategy' => 'extend',
        'allow_mid_term_downgrade' => false,
    ],

    /*
    |---------------------------------------------------------------------------
    | Privacy defaults
    |---------------------------------------------------------------------------
    | Applied at registration. Deliberately conservative — a user who never
    | opens settings should not be maximally exposed.
    */

    'privacy' => [
        'defaults' => [
            'profile_visibility' => 'members',
            'photo_visibility' => 'members',
            'contact_visibility' => 'premium',
            'show_online_status' => true,
            'appear_in_search' => true,
            'show_profile_views' => true,
            'who_can_message' => 'matches_only',
        ],

        'options' => [
            'profile_visibility' => ['all', 'members', 'premium', 'matches_only'],
            'photo_visibility' => ['all', 'members', 'premium', 'on_request'],
            'contact_visibility' => ['members', 'premium', 'matches_only'],
            'who_can_message' => ['all', 'members', 'matches_only'],
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Verification
    |---------------------------------------------------------------------------
    | The accepted ID types are config rather than master data: they are not a
    | dropdown an admin curates, they are the list the review team is trained
    | and legally comfortable to handle, and changing it is a deploy-time
    | decision.
    */

    'verification' => [
        'types' => [
            'aadhaar' => 'Aadhaar card',
            'pan' => 'PAN card',
            'passport' => 'Passport',
            'driving_licence' => 'Driving licence',
            'other' => 'Other government ID',
        ],

        // One open submission at a time. A queue of five documents from one
        // member is five reviews of the same person, and the reviewer has no
        // way to tell which one the member actually wants looked at.
        'max_pending' => 1,
    ],

    /*
    |---------------------------------------------------------------------------
    | Reports
    |---------------------------------------------------------------------------
    */

    'reports' => [
        'categories' => [
            'fake_profile' => 'Fake profile',
            'spam' => 'Spam',
            'harassment' => 'Harassment',
            'abuse' => 'Abusive behaviour',
            'inappropriate_content' => 'Inappropriate content',
            'fraud' => 'Fraud or money request',
            'other' => 'Other',
        ],

        'rejection_reasons' => [
            'photo' => ['Not a clear face photo', 'Group photo', 'Watermarked or edited', 'Inappropriate content', 'Poor quality'],
            'profile' => ['Incomplete information', 'Suspicious details', 'Contact details in free text', 'Offensive content'],
            'verification' => ['Document unreadable', 'Name mismatch', 'Expired document', 'Document type not accepted'],
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Moderation ergonomics
    |---------------------------------------------------------------------------
    |
    | The photo queue's keyboard layer advances the moderator to the next card
    | the instant a key is pressed, but holds the POST for this long so `U`
    | can take it back. It is a real, load-bearing delay, not an animation:
    | `ModeratePhoto` refuses a photo that is no longer pending, so nothing
    | can reverse a decision once it has been sent, and docs/06 §7 forbids
    | optimistic writes on anything irreversible. Zero disables the window and
    | sends immediately — which is a policy change, not a tuning knob.
    |
    | Long enough to notice a mis-key, short enough that a moderator clearing
    | a queue never waits on it (the window runs in the background; the next
    | card is already focused).
    |
    */

    'moderation' => [
        'undo_seconds' => (int) env('MODERATION_UNDO_SECONDS', 6),
    ],

    /*
    |---------------------------------------------------------------------------
    | Retention (days)
    |---------------------------------------------------------------------------
    */

    'retention' => [
        'profile_views' => 180,
        'otp' => 1,
        'soft_deleted_accounts' => 30,
        'audit_logs' => 1095,
        'unpaid_orders' => 1,

        // Generated CSVs on the private disk. The shortest window here on
        // purpose: an export is fetched within minutes of its notification or
        // never, and each surviving file is the entire member base in one
        // object. `ExportController` answers 410 once it is gone, which costs
        // the caller one click to re-run.
        'exports' => 7,
    ],

    /*
    |---------------------------------------------------------------------------
    | Mobile force-upgrade
    |---------------------------------------------------------------------------
    | The only remote kill switch we have. Raising these blocks older builds at
    | launch, which is how a client with a known payment bug gets retired.
    */

    'app' => [
        'min_build' => [
            'android' => (int) env('MIN_APP_BUILD_ANDROID', 1),
            'ios' => (int) env('MIN_APP_BUILD_IOS', 1),
        ],
        'store_url' => [
            'android' => 'https://play.google.com/store/apps/details?id=com.matrimony.app',
            'ios' => 'https://apps.apple.com/app/id000000000',
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Admin panel navigation
    |---------------------------------------------------------------------------
    | One definition, read by resources/views/admin/layouts/admin.blade.php's
    | @foreach + @can — label, the named route, an icon key `<x-admin.icon>`
    | understands, and the permission that must be true for the link (and the
    | route behind it) to exist for this staff member. Hiding the link is a
    | courtesy; the route itself carries the real Policy/Gate check — see
    | docs/06-admin-panel-guide.md §3.
    |
    | **Add an entry the same commit its route is registered, never before.**
    | A Blade view calling route() on a name that does not exist yet throws at
    | render time, and this nav renders on every admin page — see PROGRESS.md
    | §6, "Blade views must never call route() on a name that doesn't exist
    | yet." Ungated modules (13 of the 15 in docs/06 §5) are simply absent
    | from this list until the session that builds them adds their row.
    */

    'admin_nav' => [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'dashboard', 'permission' => 'dashboard.view'],
        ['label' => 'Members', 'route' => 'admin.users.index', 'icon' => 'users', 'permission' => 'users.view'],
        ['label' => 'Profile approvals', 'route' => 'admin.profiles.index', 'icon' => 'clipboard', 'permission' => 'profiles.moderate'],
        ['label' => 'Photo moderation', 'route' => 'admin.photos.index', 'icon' => 'photo', 'permission' => 'photos.moderate'],
        ['label' => 'Jadhagam moderation', 'route' => 'admin.jadhagam.index', 'icon' => 'document', 'permission' => 'photos.moderate'],
        ['label' => 'ID verification', 'route' => 'admin.verification.index', 'icon' => 'shield', 'permission' => 'verification.review'],
        ['label' => 'Reports', 'route' => 'admin.reports.index', 'icon' => 'flag', 'permission' => 'reports.view'],
        ['label' => 'Membership plans', 'route' => 'admin.plans.index', 'icon' => 'card', 'permission' => 'memberships.view'],
        ['label' => 'Subscriptions', 'route' => 'admin.subscriptions.index', 'icon' => 'refresh', 'permission' => 'subscriptions.view'],
        ['label' => 'Payments', 'route' => 'admin.payments.index', 'icon' => 'rupee', 'permission' => 'payments.view'],
        ['label' => 'Chat moderation', 'route' => 'admin.chat.index', 'icon' => 'chat', 'permission' => 'chat.moderate'],

        ['label' => 'Content', 'route' => 'admin.cms.pages.index', 'icon' => 'document', 'permission' => 'cms.view'],
        ['label' => 'Koodal Games', 'route' => 'admin.games.questions.index', 'icon' => 'clipboard', 'permission' => 'cms.view'],
        ['label' => 'Media library', 'route' => 'admin.media.index', 'icon' => 'photo', 'permission' => 'cms.view'],

        // `params` fills a route segment the nav cannot otherwise supply —
        // both of these index routes are scoped to a `{type}`, and the nav
        // link has to land on one of them. See layouts.admin.
        ['label' => 'Master data', 'route' => 'admin.master-data.index', 'params' => ['type' => 'religions'], 'icon' => 'table', 'permission' => 'master_data.view'],
        ['label' => 'Locations', 'route' => 'admin.locations.index', 'params' => ['type' => 'countries'], 'icon' => 'globe', 'permission' => 'locations.view'],

        ['label' => 'Staff & roles', 'route' => 'admin.staff.index', 'icon' => 'shield-user', 'permission' => 'staff.view'],
        ['label' => 'Settings', 'route' => 'admin.settings.index', 'icon' => 'cog', 'permission' => 'settings.view'],
        ['label' => 'Firebase Settings', 'route' => 'admin.firebase.index', 'icon' => 'cog', 'permission' => 'settings.view'],
        ['label' => 'Notification Logs', 'route' => 'admin.notification-logs.index', 'icon' => 'list', 'permission' => 'notifications.view'],
        ['label' => 'Audit log', 'route' => 'admin.audit', 'icon' => 'list', 'permission' => 'settings.audit'],
    ],

];
