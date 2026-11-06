<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationDocument;
use App\Policies\Concerns\ActsAsStaff;
use Illuminate\Auth\Access\Response;

/**
 * The most sensitive table in the schema. Every ability here is deliberately
 * narrower than it first looks.
 */
class VerificationDocumentPolicy
{
    use ActsAsStaff;

    /**
     * The *row* — status, type, remarks. The model hides `path`, `disk` and
     * `document_number_hash` from serialisation, so allowing this does not
     * expose the file.
     */
    public function view(User $user, VerificationDocument $document): Response
    {
        return $document->user_id === $user->id || $this->staffCan($user, 'verification.review')
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * The *file*, behind a five-minute signed URL and an audit row per access.
     *
     * Not granted to the owner, and that is not an oversight: the endpoint that
     * mints the URL is on the admin surface and every call to it writes an audit
     * row naming a staff member. Giving members an un-audited path to the same
     * private disk is a second door into the one place government IDs live, for
     * a file they uploaded and already have.
     */
    public function viewDocument(User $user): bool
    {
        return $this->staffCan($user, 'verification.review');
    }

    public function review(User $user, VerificationDocument $document): Response
    {
        // Reviewing an already-reviewed document silently replaces who decided
        // and when. Resubmission is the path back to `pending`, and it is the
        // member's to take.
        if (! $document->isPending()) {
            return Response::deny('This document has already been reviewed.');
        }

        return $this->staffCan($user, 'verification.review')
            ? Response::allow()
            : Response::deny();
    }

    /**
     * A member replaces their own submission while it is still pending; once it
     * is approved the document is the evidence behind their verified badge and
     * removing it would leave the badge standing on nothing.
     */
    public function delete(User $user, VerificationDocument $document): Response
    {
        if ($document->user_id !== $user->id) {
            return Response::denyAsNotFound();
        }

        return $document->isPending()
            ? Response::allow()
            : Response::deny('A reviewed document cannot be removed.');
    }
}
