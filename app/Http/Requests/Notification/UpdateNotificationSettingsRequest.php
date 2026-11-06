<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

/**
 * `types` is keyed by CHANNEL — interests, messages, membership, moderation,
 * announcements — not by individual notification type.
 *
 * That is what NotificationSetting::allows() reads, and it is also what maps
 * onto the Android notification channels a user can mute at the OS level. A
 * per-type switch would give the client fifteen toggles the OS-level controls
 * then silently override, and every new notification type would need a
 * settings-screen release before it could be turned off.
 */
class UpdateNotificationSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'push_enabled' => ['sometimes', 'boolean'],
            'email_enabled' => ['sometimes', 'boolean'],
            'sms_enabled' => ['sometimes', 'boolean'],

            // Unknown keys are rejected rather than ignored. A client sending
            // per-type keys would otherwise get a 200 and keep receiving
            // everything — the worst possible outcome for a mute switch.
            'types' => ['sometimes', 'array', function (string $attribute, mixed $value, \Closure $fail) {
                $unknown = array_diff(array_keys((array) $value), static::channels());

                if ($unknown !== []) {
                    $fail('Unknown notification channel: '.implode(', ', $unknown).'.');
                }
            }],
            'types.*' => ['boolean'],

            'quiet_hours_enabled' => ['sometimes', 'boolean'],
            // H:i, so "22:00" straight off a time picker validates as-is.
            // Stored and compared in the application timezone — quiet hours are
            // a local-time concept and the column has no offset.
            'quiet_hours_start' => ['nullable', 'required_if:quiet_hours_enabled,true', 'date_format:H:i'],
            'quiet_hours_end' => ['nullable', 'required_if:quiet_hours_enabled,true', 'date_format:H:i'],
        ];
    }

    /**
     * Derived from the notification type table, so a new channel becomes
     * settable the moment a type using it is added to config.
     *
     * @return array<int, string>
     */
    public static function channels(): array
    {
        return array_values(array_unique(array_column(
            config('matrimony.notifications.types'),
            'channel',
        )));
    }
}
