<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Moderation\ReviewVerification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewVerificationRequest;
use App\Http\Resources\Admin\AdminVerificationResource;
use App\Models\VerificationDocument;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Government IDs. The narrowest surface in the application, and every
 * restriction on it is deliberate:
 *
 *   • one document at a time — there is no bulk download, because a bulk
 *     export of government IDs is the worst thing this system could offer;
 *   • the URL is signed and lives five minutes, so a link pasted into a chat
 *     or left in a browser history is dead before it is useful;
 *   • every single view writes an audit row naming the staff member. Not every
 *     *decision* — every view. The question an incident asks is who looked,
 *     and a trail that only records outcomes cannot answer it.
 */
class VerificationManagementController extends Controller
{
    /** Long enough to open the image, short enough to be useless if it leaks. */
    protected const URL_TTL_MINUTES = 5;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewDocument', VerificationDocument::class);

        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected,resubmit'],
            'type' => ['nullable', 'string', 'max:30'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $documents = VerificationDocument::query()
            ->with([
                'user:id,profile_code,name,gender,date_of_birth,status',
                'reviewer:id,name',
            ])
            ->status($request->string('status')->toString() ?: 'pending')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->orderBy('created_at')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::paginated($documents, AdminVerificationResource::class, 'Verification queue retrieved successfully');
    }

    /**
     * The one door to the file.
     *
     * The audit row is written *before* the URL is minted. If the signing call
     * throws we have logged an access that did not happen, which is a
     * harmless false positive; the other order logs nothing when the URL is
     * successfully handed out and the response is then lost, which is a real
     * access with no trail — and that is the failure this endpoint exists to
     * prevent.
     */
    public function viewDocument(Request $request, VerificationDocument $document, AuditLogger $audit): JsonResponse
    {
        $this->authorize('viewDocument', VerificationDocument::class);

        $audit->logAccess(
            'verification.document_viewed',
            $document,
            "Viewed the {$document->type} document of member {$document->user?->profile_code}",
        );

        return ApiResponse::success([
            'url' => Storage::disk($document->disk)->temporaryUrl(
                $document->path,
                now()->addMinutes(static::URL_TTL_MINUTES),
            ),
            'expires_in_minutes' => static::URL_TTL_MINUTES,
            'mime_type' => $document->mime_type,
        ], 'Document link generated. It expires in '.static::URL_TTL_MINUTES.' minutes.');
    }

    public function action(
        ReviewVerificationRequest $request,
        VerificationDocument $document,
        string $action,
        ReviewVerification $review,
    ): JsonResponse {
        // The Policy carries the business rule too — a document is reviewed
        // once, and resubmission is the member's path back to `pending`.
        $this->authorize('review', $document);

        $document = $review($document, $action, $request->user(), $request->input('remarks'));

        return ApiResponse::success(
            new AdminVerificationResource($document->load('user:id,profile_code,name', 'reviewer:id,name')),
            match ($action) {
                'approve' => 'Verified. The badge is now on the member’s profile.',
                'resubmit' => 'The member has been asked for a clearer copy.',
                default => 'Document rejected and the member has been told why.',
            },
        );
    }
}
