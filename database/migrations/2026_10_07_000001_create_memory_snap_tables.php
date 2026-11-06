<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('memory_snap_games');
        Schema::dropIfExists('memory_snap_questions');
        Schema::dropIfExists('memory_snap_challenges');

        Schema::create('memory_snap_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('image_url', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('memory_snap_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained('memory_snap_challenges')->cascadeOnDelete();
            $table->text('question_text');
            $table->string('option_a', 255);
            $table->string('option_b', 255);
            $table->string('option_c', 255);
            $table->string('option_d', 255);
            $table->string('correct_option', 1); // 'A', 'B', 'C', 'D'
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('memory_snap_games', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->foreignId('user_one_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_two_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_one_name', 100)->default('Priya');
            $table->string('user_two_name', 100)->default('Karthik');
            $table->string('user_one_avatar', 255)->nullable();
            $table->string('user_two_avatar', 255)->nullable();
            $table->string('status', 20)->default('waiting'); // waiting, countdown, playing, completed
            $table->boolean('user_one_joined')->default(false);
            $table->boolean('user_two_joined')->default(false);
            $table->boolean('user_one_ready')->default(false);
            $table->boolean('user_two_ready')->default(false);
            $table->json('challenge_data')->nullable(); // {title, image_url}
            $table->json('questions_data')->nullable(); // [{id, question_text, option_a, ...}]
            $table->integer('current_question_index')->default(0);
            $table->json('user_one_answers')->nullable();
            $table->json('user_two_answers')->nullable();
            $table->integer('user_one_score')->default(0);
            $table->integer('user_two_score')->default(0);
            $table->json('chat_messages')->nullable();
            $table->json('celebration_event')->nullable();
            $table->json('disconnect_info')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memory_snap_games');
        Schema::dropIfExists('memory_snap_questions');
        Schema::dropIfExists('memory_snap_challenges');
    }
};
