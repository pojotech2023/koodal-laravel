<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();

            $table->string('status', 20)->default('pending');   // pending, accepted, declined, cancelled
            $table->string('message', 500)->nullable();
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // One live interest per direction. Enforced by the database rather
            // than a check-then-insert, which races under concurrent taps.
            $table->unique(['sender_id', 'receiver_id']);

            $table->index(['receiver_id', 'status', 'created_at']);
            $table->index(['sender_id', 'status', 'created_at']);
        });

        Schema::create('matches', function (Blueprint $table) {
            $table->id();

            // Canonical ordering (user_one_id < user_two_id) is enforced in
            // CreateMatch, which is what makes the unique index meaningful in
            // both directions.
            $table->foreignId('user_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_two_id')->constrained('users')->cascadeOnDelete();

            $table->timestamp('matched_at');
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->string('status', 20)->default('active');    // active, unmatched
            $table->timestamps();

            $table->unique(['user_one_id', 'user_two_id']);
            $table->index(['user_one_id', 'status']);
            $table->index(['user_two_id', 'status']);
        });

        Schema::create('shortlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shortlisted_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('note', 200)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'shortlisted_user_id']);
            $table->index(['shortlisted_user_id', 'created_at']);
        });

        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('viewed_id')->constrained('users')->cascadeOnDelete();

            $table->string('source', 20)->default('direct');    // search, match, interest, direct
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('viewed_at');
            $table->timestamps();

            // Deliberately NOT unique — repeat views are signal, and a scraping
            // pattern needs to be visible in the data. The API dedupes by
            // (viewer, day) when presenting; a scheduled job prunes old rows.
            $table->index(['viewed_id', 'viewed_at']);
            $table->index(['viewer_id', 'viewed_at']);
        });

        // Metered per target, not per view: opening the same person's number
        // twice must not cost the user twice.
        Schema::create('contact_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('viewed_id')->constrained('users')->cascadeOnDelete();
            $table->string('period', 10);              // 2026-08
            $table->timestamps();

            $table->unique(['viewer_id', 'viewed_id', 'period']);
        });

        Schema::create('blocked_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blocked_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 200)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'blocked_user_id']);

            // The reverse direction is queried just as often — "has anyone
            // blocked me" runs on every visibility check.
            $table->index('blocked_user_id');
        });

        // Precomputed nightly. Scoring a million candidates per request is
        // impossible; reading the top N from an index is trivial.
        Schema::create('match_scores', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');

            // Drives the "Same community · Same city" line on the profile card,
            // and leaves room for model attribution later.
            $table->json('breakdown')->nullable();

            $table->timestamp('computed_at');

            $table->primary(['user_id', 'candidate_id']);
            $table->index(['user_id', 'score']);
        });
    }

    public function down(): void
    {
        foreach ([
            'match_scores', 'blocked_users', 'contact_views', 'profile_views',
            'shortlists', 'matches', 'interests',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
