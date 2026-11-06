<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Metered per target per period, not per view. Opening the same person's
 * number twice must not cost the user two units of their quota — anything else
 * is a support ticket.
 */
class ContactView extends Model
{
    use HasFactory;

    // --- Relations ---------------------------------------------------------

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    public function viewed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewed_id');
    }
}
