<?php

namespace App\Providers;

use App\Events\InterestAccepted;
use App\Events\InterestDeclined;
use App\Events\InterestSent;
use App\Events\MessageSent;
use App\Events\PaymentVerified;
use App\Events\ProfileShortlisted;
use App\Events\ProfileViewed;
use App\Listeners\CreateConversationForMatch;
use App\Listeners\SendDeclineNotification;
use App\Listeners\SendInterestNotification;
use App\Listeners\SendMatchNotification;
use App\Listeners\SendMembershipReceipt;
use App\Listeners\SendMessageNotification;
use App\Listeners\SendShortlistNotification;
use App\Listeners\SendViewNotification;
use App\Models\City;
use App\Models\MembershipPlan;
use App\Models\Setting;
use App\Models\State;
use App\Observers\CacheBustingObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Domain events fan out to notifications. The Action that caused the change
     * does not know or care who listens — which is what lets a new channel be
     * added without reopening SendInterest.
     */
    protected $listen = [
        InterestSent::class => [SendInterestNotification::class],
        InterestAccepted::class => [CreateConversationForMatch::class, SendMatchNotification::class],
        // A decline is announced too. Silence reads as "still pending" and
        // produces a second interest, or a support ticket asking why there was
        // no reply — see InterestDeclined.
        InterestDeclined::class => [SendDeclineNotification::class],
        MessageSent::class => [SendMessageNotification::class],
        ProfileViewed::class => [SendViewNotification::class],
        ProfileShortlisted::class => [SendShortlistNotification::class],
        PaymentVerified::class => [SendMembershipReceipt::class],
    ];

    protected $observers = [
        City::class => [CacheBustingObserver::class],
        State::class => [CacheBustingObserver::class],
        Setting::class => [CacheBustingObserver::class],
        MembershipPlan::class => [CacheBustingObserver::class],
    ];

    public function shouldDiscoverEvents(): bool
    {
        // Explicit over magic: the map above is documentation of what happens
        // when an interest is sent, and discovery would hide it.
        return false;
    }
}
