<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thrown by StartConversation's consent guards — the receiver's
 * `who_can_message` setting and the configured `matrimony.chat.requires`
 * rule. Both refuse with 403, but for reasons a client must not conflate
 * with a paywall, which is what `$upgradeable` distinguishes.
 *
 * Guarded the same way as `FeatureLimitException` — a self-rendering
 * exception's `render()` runs before bootstrap/app.php's guarded closures
 * ever see it, so the `api/*` check has to live here too. Reachable from
 * `Web\MessageController::start` since session 13.
 */
class ChatNotAllowedException extends Exception
{
    public function __construct(string $message, public readonly bool $upgradeable = false)
    {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if (! $request->is('api/*')) {
            return back()->with('error', $this->getMessage());
        }

        // Two different failures wear the same status but need different UI:
        // "buy a plan" is a paywall sheet, "they must accept your interest
        // first" is not, and showing the wrong one converts nobody.
        return $this->upgradeable
            ? ApiResponse::upgradeRequired('can_chat', $this->getMessage())
            : ApiResponse::error($this->getMessage(), ['chat_not_allowed' => true], 403);
    }
}
