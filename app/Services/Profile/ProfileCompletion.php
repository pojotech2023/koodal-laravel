<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Models\UserProfile;

/**
 * Turns a profile into a percentage, a list of gaps and one next action.
 *
 * Weights live in config so product can retune what "complete" means without a
 * migration or a mobile release. The percentage is stored on the profile
 * because search sorts on it — recomputing it per row at query time is not an
 * option at a million rows.
 */
class ProfileCompletion
{
    /** Fields that make each section count as done. */
    protected const REQUIRED = [
        'basic' => ['height_cm', 'marital_status_id', 'profile_created_by'],
        'religious' => ['religion_id', 'mother_tongue_id'],
        'education' => ['education_id'],
        'professional' => ['occupation_id'],
        'location' => ['country_id', 'state_id', 'city_id'],
        'family' => ['family_type', 'family_status'],
        'lifestyle' => ['diet'],
        'about' => ['about_me'],
    ];

    protected const LABELS = [
        'basic' => 'Basic details',
        'photos' => 'Photos',
        'religious' => 'Religious background',
        'education' => 'Education',
        'professional' => 'Career',
        'location' => 'Location',
        'family' => 'Family information',
        'lifestyle' => 'Lifestyle',
        'about' => 'About you',
        'preferences' => 'Partner preferences',
        'aadhaar' => 'Aadhaar verification',
    ];

    /**
     * @return array{completion_percentage: int, sections: array, missing: array, next_action: array|null}
     */
    public function report(User $user): array
    {
        $profile = $user->profile;
        $weights = config('matrimony.profile.completion_weights');

        $sections = [];
        $earned = 0;

        foreach ($weights as $key => $weight) {
            $complete = $this->isComplete($key, $user, $profile);

            $sections[] = [
                'key' => $key,
                'label' => static::LABELS[$key] ?? ucfirst($key),
                'weight' => $weight,
                'complete' => $complete,
            ];

            if ($complete) {
                $earned += $weight;
            }
        }

        $incomplete = array_values(array_filter($sections, fn (array $s) => ! $s['complete']));

        // Ordered by weight so the suggested next step is the one that moves
        // the number most — the only ordering a user experiences as sensible.
        usort($incomplete, fn (array $a, array $b) => $b['weight'] <=> $a['weight']);

        $total = array_sum($weights) ?: 100;
        $computed = (int) round($earned / $total * 100);
        $finalPercentage = $user->is_aadhaar_verified ? 100 : min(100, $computed);

        return [
            'completion_percentage' => $finalPercentage,
            'sections' => $sections,
            'missing' => array_map(fn (array $s) => "Add {$s['label']}", $incomplete),
            'next_action' => $incomplete === [] ? null : [
                'label' => "Add {$incomplete[0]['label']}",
                'route' => "/profile/complete/{$incomplete[0]['key']}",
            ],
        ];
    }

    /** Recomputes and persists. Called by every section update. */
    public function recalculate(User $user): int
    {
        $percentage = $this->report($user)['completion_percentage'];

        $user->profile?->updateQuietly(['completion_percentage' => $percentage]);

        return $percentage;
    }

    protected function isComplete(string $section, User $user, ?UserProfile $profile): bool
    {
        if (! $profile) {
            return false;
        }

        return match ($section) {
            // Photos count if at least one photo has been uploaded (or approved)
            'photos' => $user->photos()->exists(),

            'preferences' => $user->preferences !== null && ! $user->preferences->isEmpty(),

            'aadhaar' => (bool) $user->is_aadhaar_verified,

            default => collect(static::REQUIRED[$section] ?? [])
                ->every(fn (string $field) => filled($profile->{$field})),
        };
    }
}
