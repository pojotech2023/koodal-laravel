<?php

namespace App\Actions\Verification;

use App\Models\User;
use App\Models\VerificationDocument;
use App\Services\Media\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * A government ID enters the system exactly here.
 *
 * Three properties this action is responsible for, in order of how badly each
 * fails if it is wrong:
 *
 *   • the file lands on the **private** disk and no code path anywhere returns
 *     a URL for it — reaching it means the audited admin endpoint that mints a
 *     five-minute signed link;
 *   • the ID *number* is never stored in readable form. It is HMAC'd, which is
 *     enough to detect the same document arriving on two accounts and useless
 *     to anyone who reads the table;
 *   • the badge is granted by review, never by upload. Nothing here touches
 *     `user_profiles.is_verified`.
 */
class SubmitVerification
{
    public function __construct(protected ImageProcessor $images) {}

    public function __invoke(
        User $user,
        string $type,
        UploadedFile $document,
        ?string $documentNumber = null,
    ): VerificationDocument {
        abort_if(
            $user->verificationDocuments()->whereIn('status', ['pending', 'resubmit'])->count()
                >= config('matrimony.verification.max_pending'),
            409,
            'You already have a document awaiting review.',
        );

        abort_if(
            (bool) $user->profile?->is_verified,
            409,
            'Your profile is already verified.',
        );

        $hash = $documentNumber ? $this->hash($documentNumber) : null;

        // The fraud control the hash exists for. One government ID belongs to
        // one person; the same number arriving on a second account is either
        // an impersonation or a duplicate account, and both are things the
        // review team must see before a badge is granted rather than after.
        if ($hash && $this->claimedByAnotherAccount($user, $hash)) {
            abort(409, 'This document is already linked to another account. Please contact support.');
        }

        // Stored before the transaction: a disk write cannot be rolled back,
        // and holding rows locked across an S3 round trip is a lock held for as
        // long as the network feels like taking.
        $stored = $this->images->storeDocument($document, $user->id);

        return DB::transaction(fn () => VerificationDocument::create([
            'user_id' => $user->id,
            'type' => $type,
            'document_number_hash' => $hash,
            'path' => $stored['path'],
            'disk' => $stored['disk'],
            'mime_type' => $stored['mime_type'],
            'size_bytes' => $stored['size_bytes'],
            'status' => 'pending',
        ]));
    }

    // --- Internals ---------------------------------------------------------

    /**
     * Keyed with the application key, not a bare hash. An Aadhaar number is 12
     * digits — a plain SHA-256 of the whole space is brute-forceable on a
     * laptop, and a leaked table of those hashes is a leaked table of Aadhaar
     * numbers. Rotating APP_KEY invalidates duplicate detection for existing
     * rows, which is the correct trade: detection is a heuristic, the numbers
     * are not recoverable either way.
     */
    protected function hash(string $documentNumber): string
    {
        $normalised = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $documentNumber) ?? '');

        return hash_hmac('sha256', $normalised, config('app.key'));
    }

    protected function claimedByAnotherAccount(User $user, string $hash): bool
    {
        return VerificationDocument::query()
            ->where('document_number_hash', $hash)
            ->where('user_id', '!=', $user->id)
            // A rejected submission does not reserve a document number —
            // otherwise one bad upload by a stranger locks a real person out of
            // verifying with their own ID forever.
            ->whereIn('status', ['pending', 'approved', 'resubmit'])
            ->exists();
    }
}
