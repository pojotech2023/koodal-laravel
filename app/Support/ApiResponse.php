<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * The single place the API envelope is built.
 *
 * Three clients parse this shape. If a controller hand-rolls
 * `response()->json([...])`, the shape drifts, and the drift surfaces as a
 * crash in a mobile build that shipped weeks ago. So: no bare json() responses
 * anywhere in the application.
 */
class ApiResponse
{
    /**
     * What to tell a member when a failure carries no sentence of its own.
     * Every client shows `message` as-is, so it has to say what happened and
     * what to do — never a bare status name or a stack-trace fragment.
     */
    public static function defaultMessage(int $status): string
    {
        return match (true) {
            $status === 400 => 'This request could not be processed. Please check the details and try again.',
            $status === 401 => 'Your session has expired. Please log in again.',
            $status === 403 => 'You do not have permission to do this.',
            $status === 404 => 'Not found.',
            $status === 405 => 'This action is not available.',
            $status === 409 => 'This has already been done or has changed. Please refresh and try again.',
            $status === 413 => 'The file is too large. Please choose a smaller file.',
            $status === 419 => 'Your session expired. Please try again.',
            $status === 422 => 'Please check the details you entered and try again.',
            $status === 429 => 'Too many attempts. Please wait a minute and try again.',
            $status === 503 => 'Koodal is being updated. Please try again in a few minutes.',
            $status >= 500 => 'We could not complete your request right now. Please try again in a moment.',
            default => 'This request could not be completed. Please try again.',
        };
    }

    public static function success(mixed $data = null, string $message = 'Request completed successfully', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return static::success($data, $message, 201);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Paginated collections keep `data` a flat array and lift pagination into
     * `meta` — clients should never have to reach into Laravel's default
     * nested `data.data`.
     */
    public static function paginated(LengthAwarePaginator $paginator, string $resource, string $message = 'Retrieved successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /** Keyset pagination — chat threads, where offset paging skips messages. */
    public static function keyset(array $items, ?int $nextCursor, string $message = 'Retrieved successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'meta' => ['next_cursor' => $nextCursor, 'has_more' => $nextCursor !== null],
        ]);
    }

    public static function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json(array_filter([
            'success' => false,
            'message' => $message,
            'errors' => $errors ?: null,
        ], fn ($v) => $v !== null), $status);
    }

    /**
     * The paywall response. Every client already handles this shape
     * generically, so a newly gated feature needs no client work — it just has
     * to return this.
     */
    public static function upgradeRequired(
        string $feature,
        string $message = 'Upgrade your membership to use this feature.',
        array $extra = [],
    ): JsonResponse {
        return static::error($message, array_merge([
            'upgrade_required' => true,
            'required_feature' => $feature,
            'suggested_plans' => SuggestedPlans::for($feature),
        ], $extra), 403);
    }
}
