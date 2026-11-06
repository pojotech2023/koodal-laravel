<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('koodal_sync_questions', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50); // Lifestyle, Food, Travel, Daily habits, Personality, Money preferences, Family/life situations, Fun hypothetical situations, Future preferences, Entertainment, Morning/Night habits
            $table->text('question_text_en');
            $table->text('question_text_ta')->nullable();
            $table->json('options'); // e.g. [{"key": "A", "label_en": "Tea", "label_ta": "தேநீர்"}, ...]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        Schema::create('koodal_sync_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_two_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending, completed
            $table->unsignedInteger('user_one_score')->default(0);
            $table->unsignedInteger('user_two_score')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->timestamps();

            $table->index(['user_one_id', 'user_two_id']);
        });

        Schema::create('koodal_sync_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('koodal_sync_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('koodal_sync_questions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('selected_option', 50);
            $table->timestamps();

            $table->unique(['session_id', 'question_id', 'user_id']);
        });

        Schema::create('koodal_sync_pair_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_two_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('koodal_sync_questions')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_one_id', 'user_two_id', 'question_id'], 'pair_question_unique');
        });

        Schema::create('koodal_find_it_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('image_a', 255);
            $table->string('image_b', 255);
            $table->json('differences'); // Array of hotspots: [{"id": 1, "x": 25.5, "y": 40.2, "radius": 8, "name": "Hat"}]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('koodal_game_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('users')->cascadeOnDelete();
            $table->string('game_type', 30); // sync, find_it, memory, spot
            $table->integer('score')->default(0);
            $table->integer('max_score')->default(100);
            $table->json('details')->nullable(); // speed, accuracy, correct/wrong taps
            $table->timestamps();

            $table->index(['user_id', 'partner_id', 'game_type']);
        });

        Schema::create('koodal_vibe_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_two_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('vibe_percentage')->default(50);
            $table->json('common_tags')->nullable();
            $table->json('games_summary')->nullable();
            $table->timestamps();

            $table->unique(['user_one_id', 'user_two_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('koodal_vibe_results');
        Schema::dropIfExists('koodal_game_scores');
        Schema::dropIfExists('koodal_find_it_challenges');
        Schema::dropIfExists('koodal_sync_pair_histories');
        Schema::dropIfExists('koodal_sync_answers');
        Schema::dropIfExists('koodal_sync_sessions');
        Schema::dropIfExists('koodal_sync_questions');
    }
};
