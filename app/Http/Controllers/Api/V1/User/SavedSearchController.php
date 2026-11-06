<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SaveSearchRequest;
use App\Http\Resources\SavedSearchResource;
use App\Models\SavedSearch;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            SavedSearchResource::collection($request->user()->savedSearches()->latest()->get())->resolve(),
            'Saved searches retrieved successfully',
        );
    }

    /**
     * Keyed on the name, so re-saving a set the user already named "Chennai
     * doctors" updates it. Creating a second row with the same name gives them
     * a list they cannot tell apart.
     */
    public function store(SaveSearchRequest $request): JsonResponse
    {
        $search = $request->user()->savedSearches()->updateOrCreate(
            ['name' => $request->string('name')->toString()],
            [
                'filters' => $request->input('filters'),
                'alert_enabled' => $request->boolean('alert_enabled'),
            ],
        );

        return ApiResponse::created(new SavedSearchResource($search), 'Search saved successfully');
    }

    /**
     * 404 rather than 403 for someone else's row: the response must not confirm
     * that the id exists.
     */
    public function destroy(SavedSearch $savedSearch, Request $request): JsonResponse
    {
        abort_unless($savedSearch->user_id === $request->user()->id, 404);

        $savedSearch->delete();

        return ApiResponse::noContent();
    }
}
