<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('flip_tile_images', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('flip_settings', function (Blueprint $table) {
            $table->id();
            $table->string('card_back_path')->nullable();
            $table->integer('turn_timeout_seconds')->default(60);
            $table->integer('preview_seconds')->default(2);
            $table->integer('mismatch_show_seconds')->default(1);
            $table->timestamps();
        });

        Schema::create('flip_games', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->json('board');
            $table->json('matched');
            $table->json('messages')->nullable();
            $table->string('p1_token');
            $table->string('p2_token')->nullable();
            $table->string('p1_name');
            $table->string('p2_name')->nullable();
            $table->integer('p1_score')->default(0);
            $table->integer('p2_score')->default(0);
            $table->unsignedTinyInteger('turn')->default(1);
            $table->unsignedSmallInteger('first_flip')->nullable();
            $table->unsignedSmallInteger('second_flip')->nullable();
            $table->timestamp('mismatch_until')->nullable();
            $table->boolean('preview_used')->default(false);
            $table->timestamp('preview_until')->nullable();
            $table->string('status', 20)->default('waiting');
            $table->timestamp('last_move_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flip_games');
        Schema::dropIfExists('flip_settings');
        Schema::dropIfExists('flip_tile_images');
    }
};
