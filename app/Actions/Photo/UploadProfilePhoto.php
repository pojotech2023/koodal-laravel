<?php

namespace App\Actions\Photo;

use App\Exceptions\FeatureLimitException;
use App\Jobs\ProcessProfilePhoto;
use App\Models\ProfilePhoto;
use App\Models\User;
use App\Services\Membership\FeatureGate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores the original and queues the processing.
 *
 * The request returns as soon as the bytes are safe on disk. Compression,
 * thumbnailing and the blurred derivative happen on the `media` queue —
 * a 5 MB photo over a patchy mobile connection is already a slow request, and
 * making the user also wait for three re-encodes is how uploads get abandoned.
 *
 * The photo limit is a *plan* limit, so it is checked against FeatureGate
 * rather than a constant, and only approved and pending photos count. Rejected
 * ones must not permanently consume a slot.
 */
class UploadProfilePhoto
{
    public function __construct(protected FeatureGate $features) {}

    public function __invoke(User $user, UploadedFile $file): ProfilePhoto
    {
        $this->assertUnderLimit($user);

        $disk = config('filesystems.default');
        $temporaryPath = "profiles/{$user->id}/originals/".Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        Storage::disk($disk)->putFileAs(dirname($temporaryPath), $file, basename($temporaryPath));

        $photo = DB::transaction(function () use ($user, $temporaryPath, $disk, $file) {
            $isFirst = ! $user->photos()->exists();

            return ProfilePhoto::create([
                'user_id' => $user->id,
                'path' => $temporaryPath,
                'disk' => $disk,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                // The first photo becomes primary automatically — a profile
                // with photos but no primary renders as a blank avatar.
                'is_primary' => $isFirst,
                'sort_order' => (int) $user->photos()->max('sort_order') + 1,
                'approval_status' => config('matrimony.profile.photo_moderation_required') ? 'pending' : 'approved',
            ]);
        });

        ProcessProfilePhoto::dispatch($photo->id);

        return $photo;
    }

    protected function assertUnderLimit(User $user): void
    {
        $limit = $this->features->limit($user, 'photo_limit')
            ?: config('matrimony.profile.photo_limit_default');

        if ($limit === -1) {
            return;
        }

        // Rejected photos do not count: a moderator's decision must not cost
        // the user a slot they paid for.
        $used = $user->photos()->whereIn('approval_status', ['approved', 'pending'])->count();

        if ($used >= $limit) {
            throw FeatureLimitException::quota('photo_limit', $used, $limit, null);
        }
    }
}
