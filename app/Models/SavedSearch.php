<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavedSearch extends Model
{
    use BelongsToUser, HasFactory;

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'alert_enabled' => 'boolean',
            'last_alerted_at' => 'datetime',
        ];
    }
}
