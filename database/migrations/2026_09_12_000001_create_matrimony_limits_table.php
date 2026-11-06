<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matrimony_limits', function (Blueprint $table) {
            $table->id();
            $table->string('plan_type', 40)->default('basic'); // basic, premium, advanced
            $table->unsignedInteger('available_user_count')->default(100);
            $table->unsignedInteger('used_count')->default(0);
            $table->integer('balance_count')->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrimony_limits');
    }
};
