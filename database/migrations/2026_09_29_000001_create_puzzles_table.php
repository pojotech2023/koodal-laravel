<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puzzles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('original_image');
            $table->string('modified_image');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('difficulty', 20)->default('medium');
            $table->unsignedInteger('time_limit_seconds')->default(180);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puzzles');
    }
};
