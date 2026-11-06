<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeviceToken extends Model
{
    use BelongsToUser, HasFactory;

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }
}
