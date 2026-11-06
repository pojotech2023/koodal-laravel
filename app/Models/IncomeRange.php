<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IncomeRange extends Model
{
    use HasFactory, IsMasterData;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_amount' => 'integer',
            'max_amount' => 'integer',
        ];
    }

    // --- Accessors ---------------------------------------------------------

    /** null max means "and above"; the midpoint is capped so it stays usable. */
    public function midpoint(): int
    {
        return $this->max_amount === null
            ? (int) ($this->min_amount * 1.5)
            : (int) (($this->min_amount + $this->max_amount) / 2);
    }

    public function overlaps(?int $min, ?int $max): bool
    {
        $ceiling = $this->max_amount ?? PHP_INT_MAX;

        return ($min === null || $ceiling >= $min)
            && ($max === null || $this->min_amount <= $max);
    }
}
