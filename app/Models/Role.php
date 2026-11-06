<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    // --- Relations ---------------------------------------------------------

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // --- Accessors ---------------------------------------------------------

    /** Built-in roles cannot be deleted: removing `user` would brick signup. */
    public function isDeletable(): bool
    {
        return ! $this->is_system;
    }
}
