<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flip_games', function (Blueprint $table) {
            $table->boolean('p1_ready')->default(false)->after('p2_name');
            $table->boolean('p2_ready')->default(false)->after('p1_ready');
        });
    }

    public function down(): void
    {
        Schema::table('flip_games', function (Blueprint $table) {
            $table->dropColumn(['p1_ready', 'p2_ready']);
        });
    }
};
