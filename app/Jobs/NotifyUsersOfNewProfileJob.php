<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserPreference;
use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyUsersOfNewProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public int $newUserId) {}

    public function handle(FirebaseNotificationService $fcm): void
    {
        $newUser = User::with(['profile', 'preferences'])->find($this->newUserId);

        if (! $newUser || $newUser->status !== 'active') {
            return;
        }

        $profile = $newUser->profile;
        if (! $profile || $profile->approval_status !== 'approved') {
            return;
        }

        // Target opposite gender active users
        $oppositeGender = $newUser->gender === 'male' ? 'female' : 'male';

        $recipients = User::query()
            ->where('gender', $oppositeGender)
            ->where('status', 'active')
            ->where('id', '!=', $newUser->id)
            ->with(['preferences'])
            ->limit(100)
            ->get();

        foreach ($recipients as $recipient) {
            try {
                $pref = $recipient->preferences;
                $isMatch = $this->matchesPreference($pref, $newUser);

                if ($isMatch) {
                    $fcm->sendToUser(
                        $recipient->id,
                        'New profile matching your preference',
                        "A new profile matching your partner preference has joined: {$newUser->name} ({$newUser->profile_code})",
                        [
                            'type' => 'preference_match',
                            'reference_id' => (string) $newUser->id,
                            'profile_id' => (string) $newUser->id,
                            'profile_code' => (string) $newUser->profile_code,
                        ],
                        'preference_match'
                    );
                } else {
                    $fcm->sendToUser(
                        $recipient->id,
                        'New Profile Joined',
                        "A new member has joined Koodal Matrimony: {$newUser->name} ({$newUser->profile_code})",
                        [
                            'type' => 'new_profile',
                            'reference_id' => (string) $newUser->id,
                            'profile_id' => (string) $newUser->id,
                            'profile_code' => (string) $newUser->profile_code,
                        ],
                        'new_profile'
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('NotifyUsersOfNewProfileJob recipient error: '.$e->getMessage());
            }
        }
    }

    protected function matchesPreference(?UserPreference $pref, User $newUser): bool
    {
        if (! $pref || $pref->isEmpty()) {
            return false;
        }

        $profile = $newUser->profile;
        if (! $profile) {
            return false;
        }

        // Age check
        $age = $newUser->age();
        if ($pref->age_min && $age < $pref->age_min) {
            return false;
        }
        if ($pref->age_max && $age > $pref->age_max) {
            return false;
        }

        // Religion check
        if (! empty($pref->religion_ids) && $profile->religion_id) {
            if (! in_array($profile->religion_id, $pref->religion_ids, false)) {
                return false;
            }
        }

        // Community check
        if (! empty($pref->community_ids) && $profile->community_id) {
            if (! in_array($profile->community_id, $pref->community_ids, false)) {
                return false;
            }
        }

        return true;
    }
}
