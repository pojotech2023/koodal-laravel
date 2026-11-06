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
        Schema::table('flip_games', function (Blueprint $table) {
            $table->unsignedBigInteger('user_one_id')->nullable()->after('code');
            $table->unsignedBigInteger('user_two_id')->nullable()->after('user_one_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flip_games', function (Blueprint $table) {
            $table->dropColumn(['user_one_id', 'user_two_id']);
        });
    }
};
