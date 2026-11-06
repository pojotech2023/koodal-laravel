<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shortlist extends Model
{
    use BelongsToUser, HasFactory;

    // --- Relations ---------------------------------------------------------

    public function shortlistedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shortlisted_user_id');
    }
}
