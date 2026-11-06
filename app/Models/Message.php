<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'attachment_meta' => 'array',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    // --- Relations ---------------------------------------------------------

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // --- Accessors ---------------------------------------------------------

    /** What the conversation list shows. Images have no body to preview. */
    public function preview(): string
    {
        return match ($this->type) {
            'image' => 'Photo',
            'system' => (string) $this->body,
            default => mb_substr((string) $this->body, 0, 160),
        };
    }

    /**
     * Moved off `MessageResource` so `Web\MessageController`'s Blade thread
     * shares the exact same signed-URL minting as the API — same reasoning
     * as `UserProfile::displayNameFor()` (PROGRESS.md §6). `null` when there
     * is no attachment; `MessageResource` still decides whether to include
     * the key at all.
     *
     * Minted at read time, never at broadcast time — a URL handed to
     * `MessageSent` would expire in the socket buffer of a backgrounded app.
     *
     * @return array{url: string, expires_in_minutes: int, width: ?int, height: ?int, mime_type: ?string, size_bytes: ?int}|null
     */
    public function attachmentPayload(): ?array
    {
        if ($this->attachment_path === null) {
            return null;
        }

        $minutes = config('matrimony.chat.image_url_ttl');
        $meta = $this->attachment_meta ?? [];

        try {
            $url = Storage::disk('private')->temporaryUrl(
                $this->attachment_path,
                now()->addMinutes($minutes),
            );
        } catch (\Throwable $e) {
            $url = Storage::disk('private')->url($this->attachment_path);
        }

        return [
            'url' => $url,
            'expires_in_minutes' => $minutes,
            'width' => $meta['width'] ?? null,
            'height' => $meta['height'] ?? null,
            'mime_type' => $meta['mime_type'] ?? null,
            'size_bytes' => $meta['size_bytes'] ?? null,
        ];
    }

    // --- Scopes ------------------------------------------------------------

    /**
     * Keyset pagination walks backwards on `id`. Paging on created_at breaks
     * when two messages share a timestamp, which on a fast exchange is common.
     */
    public function scopeBefore(Builder $query, ?int $beforeId): Builder
    {
        return $beforeId ? $query->where('id', '<', $beforeId) : $query;
    }
}
