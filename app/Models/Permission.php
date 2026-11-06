<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    public $timestamps = true;

    // --- Relations ---------------------------------------------------------

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
