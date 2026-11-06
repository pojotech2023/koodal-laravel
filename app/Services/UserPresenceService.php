<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class UserPresenceService
{
    /**
     * Touch user presence ping — stores presence in Cache and updates last_active_at.
     */
    public function touchPresence(User $user): void
    {
        $now = now();
        // 5-minute presence window in cache
        Cache::put("user_presence_{$user->id}", $now->timestamp, 300);

        try {
            $user->timestamps = false;
            $user->update(['last_active_at' => $now]);
        } catch (\Throwable $e) {
            // Silently continue if column doesn't support or error occurs
        }
    }

    /**
     * Format presence label for UI display.
     */
    public function formatStatus(?User $user): array
    {
        if (!$user) {
            return [
                'is_online' => false,
                'label'     => 'Offline',
                'dot'       => 'gray',
            ];
        }

        // A member who turned off "show online status" reads as offline with
        // no last-seen time — the rule profiles and chat already follow; the
        // game pages and /presence/{user}/status ignored it.
        if (! ($user->privacySettings?->show_online_status ?? true)) {
            return [
                'is_online' => false,
                'label'     => 'Offline',
                'dot'       => 'gray',
            ];
        }

        $now = now();

        // 1. Check Cache-based presence
        $cachedTs = Cache::get("user_presence_{$user->id}");
        $isOnline = false;

        if ($cachedTs && ($now->timestamp - $cachedTs) <= 300) {
            $isOnline = true;
        }

        // 2. Check last_active_at if cache miss
        $lastSeen = $user->last_active_at ? Carbon::parse($user->last_active_at) : null;
        if (!$isOnline && $lastSeen && $lastSeen->diffInSeconds($now) <= 300) {
            $isOnline = true;
        }

        if ($isOnline) {
            return [
                'is_online' => true,
                'label'     => 'Online',
                'dot'       => 'green',
            ];
        }

        if (!$lastSeen) {
            $lastLogin = $user->last_login_at ? Carbon::parse($user->last_login_at) : null;
            $lastSeen = $lastLogin;
        }

        if (!$lastSeen) {
            return [
                'is_online' => false,
                'label'     => 'Offline',
                'dot'       => 'gray',
            ];
        }

        if ($lastSeen->isToday()) {
            $formatted = 'Last seen at ' . $lastSeen->format('g:i A');
        } elseif ($lastSeen->isYesterday()) {
            $formatted = 'Yesterday at ' . $lastSeen->format('g:i A');
        } else {
            $formatted = $lastSeen->format('M j \a\t g:i A');
        }

        return [
            'is_online' => false,
            'label'     => $formatted,
            'dot'       => 'gray',
        ];
    }
}

