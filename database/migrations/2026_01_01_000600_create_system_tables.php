<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('type', 40);
            $table->string('title');
            $table->text('body')->nullable();

            // Carries `route` so the client's tap handler is one line and new
            // notification types become deep-linkable without an app release.
            $table->json('data')->nullable();

            $table->string('channel', 20)->default('push');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();

            $table->timestamps();

            // Serves both the unread badge and the list.
            $table->index(['user_id', 'read_at', 'created_at']);
        });

        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('push_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->json('types')->nullable();
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->timestamps();
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 255)->unique();
            $table->string('platform', 10);         // android, ios, web
            $table->string('device_id', 120)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('privacy_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('profile_visibility', 20)->default('members');
            $table->string('photo_visibility', 20)->default('members');
            $table->string('contact_visibility', 20)->default('premium');
            $table->boolean('show_online_status')->default(true);
            $table->boolean('appear_in_search')->default(true);
            $table->boolean('show_profile_views')->default(true);
            $table->string('who_can_message', 20)->default('matches_only');
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reported_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('category', 40);
            $table->text('description')->nullable();

            // Message ids snapshotted at report time, so evidence survives the
            // reported user deleting the messages.
            $table->json('evidence_paths')->nullable();
            $table->json('evidence_message_ids')->nullable();

            $table->string('status', 20)->default('open');      // open, investigating, action_taken, dismissed
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_taken', 20)->nullable();     // warned, suspended, blocked, none
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('reported_user_id');
        });

        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('og_image_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category', 60)->default('general');
            $table->string('question');
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['category', 'sort_order']);
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('image_path');
            $table->string('mobile_image_path')->nullable();
            $table->string('link_url')->nullable();
            $table->string('position', 30);         // home_hero, home_mid, app_home
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['position', 'is_active', 'sort_order']);
        });

        Schema::create('success_stories', function (Blueprint $table) {
            $table->id();
            $table->string('groom_name', 120);
            $table->string('bride_name', 120);
            $table->foreignId('groom_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('bride_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('marriage_date')->nullable();
            $table->text('story')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('location', 120)->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('contact_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('mobile', 16)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('open');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('response')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');      // string, int, bool, json
            $table->string('group', 50)->default('general');

            // Whether this key may be exposed via GET /settings/public. Default
            // false so a new setting is never accidentally published.
            $table->boolean('is_public')->default(false);

            $table->timestamps();

            $table->index('group');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('description')->nullable();

            // Secrets are redacted by AuditLogger before write — never store a
            // credential in a table people are allowed to read.
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach ([
            'audit_logs', 'settings', 'contact_enquiries', 'success_stories', 'banners',
            'faqs', 'cms_pages', 'reports', 'privacy_settings', 'device_tokens',
            'notification_settings', 'notifications',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
