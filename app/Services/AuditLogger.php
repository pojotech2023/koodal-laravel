<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Every staff mutation lands here.
 *
 * Two rules that make the log trustworthy:
 *
 *   • Secrets are redacted before write. An audit table is read by more people
 *     than any other table in the system; a password hash or a gateway secret
 *     recorded "for completeness" is a credential leak with an index on it.
 *   • Only changed attributes are stored. A diff of two identical 60-column
 *     rows is unreadable, and unreadable logs do not get read.
 */
class AuditLogger
{
    /** Substrings that mark a value as never-loggable. */
    protected const REDACT = [
        'password', 'token', 'secret', 'signature', 'code_hash',
        'document_number', 'remember_token', 'api_key',
    ];

    public function log(
        string $action,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?User $actor = null,
    ): AuditLog {
        $request = request();

        return AuditLog::create([
            'user_id' => ($actor ?? $request?->user())?->id,
            'action' => $action,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'description' => $description,
            'old_values' => $this->redact($oldValues),
            'new_values' => $this->redact($newValues),
            'ip_address' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 512),
        ]);
    }

    /** Records only what actually changed on an updated model. */
    public function logChanges(string $action, Model $subject, ?string $description = null, ?User $actor = null): ?AuditLog
    {
        $changes = $subject->getChanges();

        unset($changes['updated_at']);

        if ($changes === []) {
            return null;
        }

        $original = array_intersect_key($subject->getOriginal(), $changes);

        return $this->log($action, $subject, $original, $changes, $description, $actor);
    }

    /** A read that is itself sensitive — viewing a government ID, opening a chat. */
    public function logAccess(string $action, Model $subject, string $description): AuditLog
    {
        return $this->log($action, $subject, null, null, $description);
    }

    protected function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach ($values as $key => $value) {
            foreach (static::REDACT as $needle) {
                if (str_contains(strtolower((string) $key), $needle)) {
                    $values[$key] = '[redacted]';

                    continue 2;
                }
            }

            if (is_array($value)) {
                $values[$key] = $this->redact($value);
            }
        }

        return $values;
    }
}
