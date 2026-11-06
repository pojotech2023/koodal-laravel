<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->nullable()->constrained('matches')->nullOnDelete();

            // Same canonical ordering as `matches`.
            $table->foreignId('user_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_two_id')->constrained('users')->cascadeOnDelete();

            // Denormalised so the conversation list — the most-polled screen in
            // the app — is one indexed read with no joins or subqueries.
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_preview', 160)->nullable();
            $table->foreignId('last_message_sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('user_one_unread_count')->default(0);
            $table->unsignedInteger('user_two_unread_count')->default(0);
            $table->unsignedInteger('messages_count')->default(0);

            $table->string('status', 20)->default('active');    // active, archived, blocked
            $table->timestamps();

            $table->unique(['user_one_id', 'user_two_id']);
            $table->index(['user_one_id', 'last_message_at']);
            $table->index(['user_two_id', 'last_message_at']);
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->foreign('conversation_id')->references('id')->on('conversations')->nullOnDelete();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();

            $table->string('type', 20)->default('text');        // text, image, system
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->json('attachment_meta')->nullable();

            $table->string('status', 20)->default('sent');      // sent, delivered, read
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();

            // Client-generated UUID. On Indian mobile networks a request often
            // succeeds while the response is lost; without this, every retry
            // produces a duplicate message.
            $table->uuid('client_message_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Keyset pagination walks backwards on `id` — monotonic and unique,
            // unlike created_at.
            $table->index(['conversation_id', 'id']);
            $table->unique(['conversation_id', 'client_message_id']);
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
        });

        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
