<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Runtime knobs an admin can change without a deploy.
 *
 * `is_public` defaults to false and is set true only where a client genuinely
 * needs the value — a settings table that publishes everything by default is
 * one careless row away from leaking an internal threshold or a support email
 * meant for staff.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // group, key, value, type, public
            ['general', 'site_name', 'Matrimony', 'string', true],
            ['general', 'site_tagline', 'Find someone who fits your life', 'string', true],
            ['general', 'support_email', 'support@matrimony.test', 'string', true],
            ['general', 'support_phone', '+919000000000', 'string', true],
            ['general', 'office_address', 'Coimbatore, Tamil Nadu, India', 'string', true],
            ['general', 'maintenance_message', '', 'string', true],

            ['social', 'facebook_url', '', 'string', true],
            ['social', 'instagram_url', '', 'string', true],
            ['social', 'youtube_url', '', 'string', true],
            ['social', 'whatsapp_number', '', 'string', true],

            ['registration', 'otp_required', 'true', 'bool', true],
            ['registration', 'min_age_male', '21', 'int', true],
            ['registration', 'min_age_female', '18', 'int', true],
            ['registration', 'allow_email_signup', 'true', 'bool', true],

            // Public because the clients enforce it: `RequireAuth` on the website
            // and the Flutter route guard both redirect to the completion wizard
            // below this number, and a client guessing at it disagrees with the
            // server the first time it changes. Mirrors
            // `config('matrimony.profile.min_completion_to_browse')` — the config
            // value is what the API enforces; this row is how a client learns it.
            // Safe to publish: it tells a visitor how complete a profile must be,
            // which is exactly what the wizard already tells them.
            ['registration', 'min_completion_to_browse', '40', 'int', true],

            ['moderation', 'profile_approval_required', 'true', 'bool', false],
            ['moderation', 'photo_moderation_required', 'true', 'bool', false],
            ['moderation', 'auto_approve_after_hours', '24', 'int', false],

            // Not public: a scraper that knows the exact daily cap can pace
            // itself to stay just underneath it.
            ['limits', 'free_daily_profile_views', '10', 'int', false],
            ['limits', 'max_photos_free', '3', 'int', false],
            ['limits', 'search_results_cap', '500', 'int', false],

            ['payments', 'currency', 'INR', 'string', true],
            ['payments', 'gst_percentage', '18', 'int', false],
            ['payments', 'invoice_prefix', 'INV', 'string', false],
            ['payments', 'refund_window_days', '7', 'int', true],

            ['seo', 'meta_title', 'Matrimony — Trusted Matrimonial Matchmaking', 'string', true],
            ['seo', 'meta_description', 'Verified profiles, real privacy controls and matches that fit your preferences.', 'string', true],
            ['seo', 'og_image', '', 'string', true],

            ['features', 'chat_enabled', 'true', 'bool', true],
            ['features', 'success_stories_enabled', 'true', 'bool', true],
            ['features', 'horoscope_enabled', 'false', 'bool', true],
        ];

        foreach ($settings as [$group, $key, $value, $type, $isPublic]) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => $type, 'group' => $group, 'is_public' => $isPublic],
            );
        }
    }
}
