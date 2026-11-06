<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MotherTongue extends Model
{
    use HasFactory, IsMasterData;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
