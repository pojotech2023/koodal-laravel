<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory, IsMasterData;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // --- Relations ---------------------------------------------------------

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }
}
