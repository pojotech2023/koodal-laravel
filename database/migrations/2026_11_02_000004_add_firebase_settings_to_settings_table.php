<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();
        $defaults = [
            [
                'key' => 'firebase_project_id',
                'value' => env('FIREBASE_PROJECT_ID', ''),
                'type' => 'string',
                'group' => 'firebase',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'firebase_credentials_path',
                'value' => env('FIREBASE_CREDENTIALS', 'storage/app/private/firebase/service-account.json'),
                'type' => 'string',
                'group' => 'firebase',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'firebase_sound_name',
                'value' => 'default',
                'type' => 'string',
                'group' => 'firebase',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'firebase_android_channel_id',
                'value' => 'koodal_notifications',
                'type' => 'string',
                'group' => 'firebase',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'firebase_push_enabled',
                'value' => 'true',
                'type' => 'bool',
                'group' => 'firebase',
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($defaults as $row) {
            $exists = DB::table('settings')->where('key', $row['key'])->exists();
            if (! $exists) {
                DB::table('settings')->insert($row);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('group', 'firebase')->delete();
        }
    }
};
