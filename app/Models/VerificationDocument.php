<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Government IDs. `path` and `disk` are hidden from every serialisation by
 * default — the only way to reach the file is the audited admin endpoint that
 * mints a five-minute signed URL. No accessor here returns a URL, deliberately.
 */
class VerificationDocument extends Model
{
    use BelongsToUser, HasFactory;

    protected $hidden = ['path', 'disk', 'document_number_hash'];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    // --- Relations ---------------------------------------------------------

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // --- Accessors ---------------------------------------------------------

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'resubmit'], true);
    }

    // --- Scopes ------------------------------------------------------------

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }
}
