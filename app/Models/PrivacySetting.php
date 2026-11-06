<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrivacySetting extends Model
{
    use BelongsToUser, HasFactory;

    protected function casts(): array
    {
        return [
            'show_online_status' => 'boolean',
            'appear_in_search' => 'boolean',
            'show_profile_views' => 'boolean',
        ];
    }

    /**
     * A user with no row yet is treated as holding the configured defaults
     * rather than as having no privacy — the safe direction to fail.
     */
    public static function defaults(): array
    {
        return config('matrimony.privacy.defaults');
    }
}
