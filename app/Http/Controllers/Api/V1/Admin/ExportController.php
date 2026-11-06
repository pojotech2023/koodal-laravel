<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminExportResource;
use App\Models\Notification;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The missing half of the export story.
 *
 * `ExportUsers` and `ExportPayments` write a CSV to the private disk and notify
 * the requester with the path. Until this controller existed nothing could
 * fetch the file, so the feature ended at "a path in a notification payload".
 *
 * Four rules, each closing a specific hole:
 *
 *   1. **The client never supplies a path.** It names a notification; the path
 *      is read from that row server-side. The private disk also holds
 *      government IDs under `private/verifications/`, so an endpoint that took
 *      a path — with any amount of `../` filtering — would put the verification
 *      store one string away from anyone holding `users.export`. There is no
 *      filtering here to get wrong, because there is no input to filter.
 *
 *   2. **Only the requester downloads.** Not the requester's colleagues and not
 *      a super admin. The audit row says *this* staff member extracted the
 *      member base; if anyone else can pull the same file, that row is wrong
 *      about who holds the data, and the trail is the only reason the export is
 *      permitted at all. Someone else who needs it runs their own export and
 *      signs their own name to it.
 *
 *   3. **The permission is re-checked at download time**, against the export's
 *      subject rather than against this endpoint. Revoking `users.export` from
 *      a staff member has to stop them fetching yesterday's file too, or the
 *      revocation is theatre.
 *
 *   4. **Every download writes an audit row**, like every verification document
 *      view. Generating the file and taking a copy of it are separate events
 *      and an incident asks about the second one.
 */
class ExportController extends Controller
{
    /**
     * Longer than the 5 minutes a verification document gets. A member CSV is
     * megabytes rather than a JPEG, and S3 checks the signature when the
     * transfer *starts*, so the window has to cover a click on a slow office
     * connection — not the transfer itself.
     */
    protected const URL_TTL_MINUTES = 10;

    /**
     * Which permission each export subject demands. The subject string is
     * whatever the job handed `announceExport`, so this map and those two calls
     * are the contract; a third export job that forgets to add a row here is
     * refused rather than waved through, which is the correct default.
     */
    protected const PERMISSIONS = [
        'members' => 'users.export',
        'payments' => 'payments.export',
    ];

    /**
     * The caller's own finished exports, newest first.
     *
     * Scoped to the caller for the same reason the download is, and it doubles
     * as the recovery path: a notification dismissed on a phone is still listed
     * here, so a dismissed announcement does not orphan the file.
     */
    public function index(Request $request): JsonResponse
    {
        // No `authorize()` here, and that is not an omission. The list is
        // already scoped to the caller's own rows, so a moderator holding
        // neither export permission sees an empty list rather than a 403 — which
        // is the truthful answer. The permission gate belongs on `download`,
        // where a file actually changes hands.
        $exports = Notification::query()
            ->where('user_id', $request->user()->id)
            ->where('type', 'export_ready')
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        // One `exists()` per row against the private disk. Bounded by the page
        // size, and worth it: an export list whose entries mostly 404 on click
        // is worse than no list, because the caller cannot tell a swept file
        // from a broken endpoint.
        foreach ($exports->getCollection() as $export) {
            $path = $export->data['path'] ?? null;

            $export->available = $path ? Storage::disk('private')->exists($path) : false;
        }

        return ApiResponse::paginated($exports, AdminExportResource::class, 'Exports retrieved successfully');
    }

    /**
     * Mints the URL.
     *
     * Audit before signing, the same ordering `VerificationManagementController`
     * uses and for the same reason: logging an access that then failed is a
     * harmless false positive, while handing out a working URL and losing the
     * response is a real extraction with no trail.
     */
    public function download(Request $request, Notification $notification, AuditLogger $audit): JsonResponse
    {
        // 404, not 403 — someone else's notification id should not be
        // distinguishable from one that does not exist.
        abort_unless(
            $notification->user_id === $request->user()->id && $notification->type === 'export_ready',
            404,
        );

        $subject = $notification->data['subject'] ?? null;
        $path = $notification->data['path'] ?? null;

        abort_if($path === null, 404, 'This export has no file.');

        // Rule 3. `authorize()` on a bare permission string goes through the
        // Gates registered in AuthServiceProvider, so a super admin passes here
        // via Gate::before — which is correct: the restriction that binds them
        // is rule 2 above, enforced by the ownership check.
        $this->authorize(static::PERMISSIONS[$subject] ?? 'settings.manage');

        // Swept by PruneStaleRecords, or never written because the job died
        // between the CSV and the notification. Either way the caller needs to
        // hear "gone", not a signed URL that S3 answers with an XML error page.
        abort_unless(
            Storage::disk('private')->exists($path),
            410,
            'This export has expired. Run it again to get a fresh file.',
        );

        $audit->logAccess(
            'export.downloaded',
            $notification,
            "Downloaded the {$subject} export ".basename($path),
        );

        return ApiResponse::success([
            'url' => Storage::disk('private')->temporaryUrl(
                $path,
                now()->addMinutes(static::URL_TTL_MINUTES),
                // Forces a download with the original name instead of rendering
                // in the tab. Without it a browser opens a 40 MB CSV inline.
                ['ResponseContentDisposition' => 'attachment; filename="'.basename($path).'"'],
            ),
            'filename' => basename($path),
            'expires_in_minutes' => static::URL_TTL_MINUTES,
        ], 'Download link generated. It expires in '.static::URL_TTL_MINUTES.' minutes.');
    }
}
