<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Education extends Model
{
    use HasFactory, IsMasterData;

    protected $table = 'educations';

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * Ordinal rank of the education category. The match algorithm scores an
     * adjacent tier as a partial match, which needs a comparable number rather
     * than a string.
     */
    public function tier(): int
    {
        return match ($this->category) {
            'school' => 1,
            'diploma' => 2,
            'graduate' => 3,
            'post_graduate' => 4,
            'doctorate' => 5,
            default => 3,
        };
    }
}
