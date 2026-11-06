<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class NotificationSetting extends Model
{
    use BelongsToUser, HasFactory;

    protected function casts(): array
    {
        return [
            'push_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'types' => 'array',
            'quiet_hours_enabled' => 'boolean',
        ];
    }

    // --- Accessors ---------------------------------------------------------

    /** A type absent from the map is opted in — new types default to on. */
    public function allows(string $type): bool
    {
        $channel = config("matrimony.notifications.types.{$type}.channel");

        return (bool) ($this->types[$channel] ?? true);
    }

    /**
     * Quiet hours wrap midnight far more often than not (22:00–07:00), so the
     * comparison has to handle start > end rather than assuming a same-day range.
     */
    public function inQuietHours(?Carbon $at = null): bool
    {
        if (! $this->quiet_hours_enabled || ! $this->quiet_hours_start || ! $this->quiet_hours_end) {
            return false;
        }

        $now = ($at ?? now())->format('H:i:s');
        $start = Carbon::parse($this->quiet_hours_start)->format('H:i:s');
        $end = Carbon::parse($this->quiet_hours_end)->format('H:i:s');

        return $start <= $end
            ? ($now >= $start && $now < $end)
            : ($now >= $start || $now < $end);
    }
}
