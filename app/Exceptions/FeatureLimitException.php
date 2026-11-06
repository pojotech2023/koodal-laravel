<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown by FeatureGate. Renders itself as the standard upgrade envelope, so
 * every client's generic paywall handler fires without any per-feature work.
 *
 * A self-rendering exception's `render()` runs before the guarded closures in
 * bootstrap/app.php ever see it (Laravel checks `method_exists($e, 'render')`
 * first), so the `api/*` guard has to live here too, same fix as
 * PROGRESS.md §6's "exception renderers assume every route is an API route"
 * — otherwise a member hitting a plan limit on a `Web\*` route (first reached
 * this session, by the photo-upload step of the profile wizard) gets a raw
 * JSON body instead of a redirect back to the form with a flashed message.
 */
class FeatureLimitException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $feature,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public static function capability(string $feature): self
    {
        $label = config("matrimony.membership.features.{$feature}.label", $feature);

        return new self("Upgrade your membership to use {$label}.", $feature);
    }

    public static function quota(string $feature, int $used, int $limit, ?string $resetsAt): self
    {
        $label = strtolower(config("matrimony.membership.features.{$feature}.label", $feature));

        return new self(
            "You have used all {$limit} {$label} in your current plan.",
            $feature,
            ['used' => $used, 'limit' => $limit, 'resets_at' => $resetsAt],
        );
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->is('api/*')) {
            return back()->with('error', $this->getMessage());
        }

        return ApiResponse::upgradeRequired($this->feature, $this->getMessage(), $this->context);
    }
}
