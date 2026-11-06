<?php

namespace App\Services\Matching;

/**
 * @param  int  $value  0–100
 * @param  array<int, string>  $breakdown  reasons shown to the user
 */
readonly class MatchScore
{
    public function __construct(
        public int $value,
        public array $breakdown = [],
    ) {}

    public function toArray(): array
    {
        return ['score' => $this->value, 'reasons' => $this->breakdown];
    }
}
