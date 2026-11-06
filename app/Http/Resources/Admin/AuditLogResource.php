<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A trail entry, rendered for humans.
 *
 * `auditable_type` is a fully-qualified class name in the database, which is
 * the right thing to store and the wrong thing to show — it leaks the
 * application's internal namespace into a UI and reads as noise. It is
 * shortened here rather than in the log, because changing what is *stored*
 * would break `AuditLog::scopeForAuditable`, which matches on the class name.
 *
 * The values are already redacted by `AuditLogger` on write. Nothing further
 * is filtered here, deliberately: a second, differently-shaped redaction pass
 * in the read path is how a field ends up hidden in the UI while sitting in
 * plain text in the table everybody exports.
 */
class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'description' => $this->description,

            'actor' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),

            'subject' => $this->auditable_type ? [
                'type' => class_basename($this->auditable_type),
                'id' => $this->auditable_id,
            ] : null,

            'old_values' => $this->old_values,
            'new_values' => $this->new_values,

            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
