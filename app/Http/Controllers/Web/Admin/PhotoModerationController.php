<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Moderation\ModeratePhoto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkPhotoModerationRequest;
use App\Http\Requests\Admin\RejectPhotoRequest;
use App\Models\ProfilePhoto;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * PhotoModerationController} — same queue, same oldest-first order, same
 * `ProfilePhotoPolicy::moderate` ability, same `ModeratePhoto` Action and its
 * `bulk()` for the batch path, including the "report skips rather than fail
 * the batch" contract.
 *
 * **The plain, no-JS version was deliberately built first** —
 * docs/06-admin-panel-guide.md §6 asks for exactly that before the
 * keyboard-driven layer (`A` approve, `R` reject, `→` next) goes on top, the
 * same rule chat and the confirm dialog already followed. Every action here
 * is a form POST that reloads the grid, and that path still works on its own
 * with the script blocked or broken.
 *
 * The keyboard layer landed in `resources/js/admin.js` and, as promised,
 * needed **no new routes** — it `fetch()`es these same two, which answer JSON
 * instead of a redirect when the caller asks for it (see `decided()`). What
 * it does *not* do is fire early: the undo window holds the request in the
 * browser and only then POSTs, because a decision here is irreversible
 * through this UI (`ModeratePhoto` refuses a non-pending photo, which is the
 * `stale` branch below) and §7 forbids optimistic writes on anything
 * irreversible. Optimistic *advance*, delayed *write*.
 *
 * A grid, not a one-at-a-time queue like Profiles: a photo decision takes a
 * glance, not a read, so the throughput win is seeing forty at once and
 * ticking the bad ones — which is what the bulk bar is for.
 */
class PhotoModerationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('moderate', ProfilePhoto::class);

        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'gender' => ['nullable', 'in:male,female'],
        ]);

        $status = $request->string('status')->toString() ?: 'pending';

        $photos = ProfilePhoto::query()
            ->where('approval_status', $status)
            ->with(['user:id,profile_code,name,gender', 'moderator:id,name'])
            ->when($request->filled('gender'), fn ($q) => $q->whereHas(
                'user',
                fn ($u) => $u->where('gender', $request->input('gender')),
            ))

            // Oldest first: a photo that waits is a profile rendering as a
            // blank avatar, and newest-first starves the tail of the queue.
            ->orderBy('created_at')

            // 40, matching the API sibling's default page size — one screen of
            // thumbnails at a glance, and comfortably under
            // BulkPhotoModerationRequest's 100-id cap, so "select all on this
            // page" can never build a batch the request would reject.
            ->paginate(40)
            ->withQueryString();

        return view('admin.photos.index', [
            'photos' => $photos,
            'status' => $status,
            'reasons' => config('matrimony.reports.rejection_reasons.photo', []),
            'pendingCount' => ProfilePhoto::query()->where('approval_status', 'pending')->count(),
        ]);
    }

    public function approve(Request $request, ProfilePhoto $photo, ModeratePhoto $moderate): RedirectResponse|JsonResponse
    {
        $this->authorize('moderate', ProfilePhoto::class);

        if ($photo->approval_status !== 'pending') {
            return $this->decided($request, 'That photo had already been reviewed by someone else.', stale: true);
        }

        $moderate($photo, 'approve', $request->user());

        return $this->decided($request, 'Photo approved.');
    }

    public function reject(RejectPhotoRequest $request, ProfilePhoto $photo, ModeratePhoto $moderate): RedirectResponse|JsonResponse
    {
        $this->authorize('moderate', ProfilePhoto::class);

        if ($photo->approval_status !== 'pending') {
            return $this->decided($request, 'That photo had already been reviewed by someone else.', stale: true);
        }

        $moderate($photo, 'reject', $request->user(), $request->reason());

        return $this->decided($request, 'Photo rejected and the member has been told why.');
    }

    /**
     * The batch submit behind `<x-admin.bulk-bar>`.
     *
     * `ModeratePhoto::bulk()` already does the only thing that matters here:
     * one transaction, one audit row and one notification *per photo*, and
     * ids it could not moderate come back named rather than failing the whole
     * call. That last part is what this reports in the flash — a moderator who
     * ticked forty and cleared thirty-eight needs to know two were decided in
     * another tab, not to wonder whether the batch worked.
     */
    public function bulk(BulkPhotoModerationRequest $request, ModeratePhoto $moderate): RedirectResponse
    {
        $this->authorize('moderate', ProfilePhoto::class);

        $result = $moderate->bulk(
            ids: $request->input('ids'),
            decision: $request->string('action')->toString(),
            moderator: $request->user(),
            reason: $request->input('reason'),
        );

        $message = "{$result['moderated']} photos moderated.";

        if ($skipped = count($result['skipped'])) {
            $message .= " {$skipped} were skipped — already reviewed by someone else.";
        }

        return back()->with('status', $message);
    }

    // --- Internals ---------------------------------------------------------

    /**
     * One decision, two callers.
     *
     * The plain path (docs/06 §6's no-JS version, still the only path if the
     * script fails to load) wants `back()` with a flash. The keyboard layer in
     * `resources/js/admin.js` wants an answer it can act on without paying for
     * a full grid re-render per keystroke — forty decisions a minute is the
     * point of that screen.
     *
     * `ApiResponse`, not `response()->json()`, even though this is a `Web\*`
     * controller and not the API: the "one envelope, built in one place" rule
     * (PROGRESS.md §5) is about never hand-rolling a JSON shape, and it holds
     * wherever the JSON is emitted. Nothing else about this is API-shaped —
     * there is no Resource here, the route is session-guarded, and no mobile
     * client will ever call it.
     *
     * `stale: true` is not an error and deliberately does not answer 4xx: the
     * photo was moderated, just not by this request. The script uses the flag
     * to mark that one card as already-decided instead of restoring it, which
     * is the honest outcome of two moderators reaching the same photo.
     */
    protected function decided(Request $request, string $message, bool $stale = false): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return ApiResponse::success(['stale' => $stale], $message);
        }

        return back()->with('status', $message);
    }
}
