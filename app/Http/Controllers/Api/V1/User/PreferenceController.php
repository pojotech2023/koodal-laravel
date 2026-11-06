<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Preference\UpdatePreferenceRequest;
use App\Jobs\RecomputeMatchScoresForUser;
use App\Services\Profile\ProfileCompletion;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $preferences = $request->user()->preferences ?? $request->user()->preferences()->create([]);

        return ApiResponse::success($preferences, 'Partner preferences retrieved successfully');
    }

    /**
     * A preference edit invalidates this user's precomputed scores, so a
     * targeted recompute is queued rather than waiting for the nightly job —
     * otherwise someone who narrows their preferences keeps seeing yesterday's
     * recommendations until 02:30 and concludes the filter is broken.
     */
    public function update(UpdatePreferenceRequest $request, ProfileCompletion $completion): JsonResponse
    {
        $user = $request->user();

        $preferences = $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated(),
        );

        $completion->recalculate($user);

        RecomputeMatchScoresForUser::dispatch($user->id);

        return ApiResponse::success($preferences->refresh(), 'Partner preferences saved successfully');
    }
}
