<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Invites Table
        Schema::create('game_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->string('game_type', 30)->default('sync');
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->unsignedTinyInteger('reminders_sent')->default(0);
            $table->timestamp('last_reminder_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['sender_id', 'receiver_id', 'status']);
        });

        // 2. Rooms Table
        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_code', 36)->unique();
            $table->foreignId('user_a_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_b_id')->constrained('users')->cascadeOnDelete();
            $table->string('game_type', 30)->default('sync');
            $table->enum('status', ['lobby', 'playing', 'completed', 'abandoned'])->default('lobby');
            $table->boolean('user_a_ready')->default(false);
            $table->boolean('user_b_ready')->default(false);
            $table->unsignedInteger('current_question_index')->default(0);
            $table->json('question_ids')->nullable();
            $table->json('user_a_answers')->nullable();
            $table->json('user_b_answers')->nullable();
            $table->integer('user_a_score')->default(0);
            $table->integer('user_b_score')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['user_a_id', 'user_b_id', 'status']);
        });

        // 3. Chat Messages Table
        Schema::create('game_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('game_rooms')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->enum('type', ['text', 'quick_reply', 'system'])->default('text');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_chat_messages');
        Schema::dropIfExists('game_rooms');
        Schema::dropIfExists('game_invites');
    }
};
