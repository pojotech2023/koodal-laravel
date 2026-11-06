<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Moderation\ReviewVerification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewVerificationRequest;
use App\Models\VerificationDocument;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * The `Web\Admin` sibling of {@see \App\Http\Controllers\Api\V1\Admin\
 * VerificationManagementController} — same queue, same
 * `VerificationDocumentPolicy` abilities, same `ReviewVerification` Action and
 * its three outcomes (approve / reject / resubmit).
 *
 * **The one structural difference, and it is on purpose:** the API needs a
 * separate `viewDocument` endpoint because its client fetches a URL and then
 * renders it. A server-rendered page has no such round trip — so `show()`
 * itself mints the five-minute signed URL and writes the audit row, which
 * makes *opening the review page* the audited event. That is the same rule the
 * API states ("every view, not every decision"), landing on the only moment
 * this surface has. It over-reports: a reviewer who opens the page only to
 * read the previous remarks is logged as having looked at the document. The
 * API sibling's docblock already argues that direction of error is the right
 * one — a false positive in the trail is harmless, a real access with no trail
 * is the failure this whole surface exists to prevent.
 *
 * Nothing here bulk-anythings, and there is deliberately no route that lists
 * or downloads more than one document. A bulk export of government IDs is the
 * worst thing this system could offer.
 */
class VerificationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewDocument', VerificationDocument::class);

        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected,resubmit'],
            'type' => ['nullable', 'string', 'max:30'],
        ]);

        $status = $request->string('status')->toString() ?: 'pending';

        $documents = VerificationDocument::query()
            ->with(['user:id,profile_code,name,gender,date_of_birth,status', 'reviewer:id,name'])
            ->status($status)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->orderBy('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.verification.index', [
            'documents' => $documents,
            'status' => $status,
            'types' => config('matrimony.verification.types', []),
            'pendingCount' => VerificationDocument::query()->where('status', 'pending')->count(),
        ]);
    }

    /**
     * The one door to the file — audited before the URL is minted.
     *
     * That order is the API sibling's and it is not incidental: if
     * `temporaryUrl()` throws we have logged an access that did not happen,
     * which is a harmless false positive. The other order logs nothing when
     * the URL is handed out and the response is then lost, which is a real
     * access with no trail.
     */
    public function show(VerificationDocument $document, AuditLogger $audit): View
    {
        $this->authorize('viewDocument', VerificationDocument::class);

        $document->load(['user:id,profile_code,name,email,mobile,gender,date_of_birth,status', 'reviewer:id,name']);

        // `media.document`, not `verification.*` — the TTL is a property of
        // the stored file, and that is where config/matrimony.php keeps it.
        // The API sibling hardcodes 5 in a class constant; reading the config
        // here means the two cannot be tuned apart by accident.
        $ttl = (int) config('matrimony.media.document.signed_url_ttl', 5);

        $audit->logAccess(
            'verification.document_viewed',
            $document,
            "Viewed the {$document->type} document of member {$document->user?->profile_code}",
        );

        return view('admin.verification.show', [
            'document' => $document,
            'url' => Storage::disk($document->disk)->temporaryUrl($document->path, now()->addMinutes($ttl)),
            'ttl' => $ttl,
            'types' => config('matrimony.verification.types', []),
            'remarkSuggestions' => config('matrimony.reports.rejection_reasons.verification', []),
        ]);
    }

    /**
     * One route, three decisions — mirroring the API's `action()`, including
     * the route parameter *name*: `ReviewVerificationRequest` decides whether
     * remarks are mandatory by reading `$this->route('action')`, so renaming
     * that segment here would silently make remarks optional on a rejection.
     */
    public function action(
        ReviewVerificationRequest $request,
        VerificationDocument $document,
        string $action,
        ReviewVerification $review,
    ): RedirectResponse {
        // The Policy carries the business rule too — a document is reviewed
        // once, and resubmission is the member's path back to `pending`.
        $this->authorize('review', $document);

        $review($document, $action, $request->user(), $request->input('remarks'));

        return redirect()->route('admin.verification.index')->with('status', match ($action) {
            'approve' => 'Verified. The badge is now on the member’s profile.',
            'resubmit' => 'The member has been asked for a clearer copy.',
            default => 'Document rejected and the member has been told why.',
        });
    }
}
