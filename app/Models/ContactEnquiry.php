<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The public contact form's row. Written by `CmsController::contact` and worked
 * by `ContactEnquiryController`.
 *
 * Note there is no `user_id`: an enquirer is usually not a member, so the row
 * carries its own name, email and mobile. `ContactEnquiryController` matches the
 * email against the member base at read time, which is a heuristic rather than a
 * relation for exactly that reason.
 */
class ContactEnquiry extends Model
{
    use HasFactory;

    /**
     * `open` → `in_progress` → `responded`, with `closed` as the exit for the
     * ones that need no reply. Set by `Actions/Enquiry/HandleEnquiry`, which is
     * the only writer after the public form.
     *
     * @var array<int, string>
     */
    public const STATUSES = ['open', 'in_progress', 'responded', 'closed'];

    // --- Relations ---------------------------------------------------------

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    // --- Scopes ------------------------------------------------------------

    /** Same nullable-passthrough shape as Report::scopeStatus and Interest::scopeStatus. */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    /** Everything not yet answered or closed — the working queue. */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }
}
