<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puzzle_differences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('puzzle_id')->constrained('puzzles')->cascadeOnDelete();
            $table->string('label');
            $table->decimal('x_pct', 8, 4);
            $table->decimal('y_pct', 8, 4);
            $table->decimal('radius_pct', 8, 4)->default(5.0000);
            $table->string('animation_type', 30)->default('fly_away'); // fly_away, fall, swing_open, drift, pulse, blink, fade_out, lottie
            $table->enum('animate_on', ['original', 'modified'])->default('modified');
            $table->string('sprite_path')->nullable();
            $table->decimal('sprite_x_pct', 8, 4)->nullable();
            $table->decimal('sprite_y_pct', 8, 4)->nullable();
            $table->decimal('sprite_w_pct', 8, 4)->nullable();
            $table->decimal('sprite_h_pct', 8, 4)->nullable();
            $table->string('lottie_path')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puzzle_differences');
    }
};
