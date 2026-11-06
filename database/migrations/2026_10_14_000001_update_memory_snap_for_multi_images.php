<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('memory_snap_questions')) {
            Schema::table('memory_snap_questions', function (Blueprint $table) {
                if (!Schema::hasColumn('memory_snap_questions', 'image_url')) {
                    $table->string('image_url', 255)->nullable()->after('challenge_id');
                }
            });
        }

        if (Schema::hasTable('memory_snap_games')) {
            Schema::table('memory_snap_games', function (Blueprint $table) {
                if (!Schema::hasColumn('memory_snap_games', 'image_started_at')) {
                    $table->double('image_started_at')->nullable()->after('status');
                }
                if (!Schema::hasColumn('memory_snap_games', 'next_game_url')) {
                    $table->string('next_game_url', 255)->nullable()->after('celebration_event');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('memory_snap_questions')) {
            Schema::table('memory_snap_questions', function (Blueprint $table) {
                if (Schema::hasColumn('memory_snap_questions', 'image_url')) {
                    $table->dropColumn('image_url');
                }
            });
        }

        if (Schema::hasTable('memory_snap_games')) {
            Schema::table('memory_snap_games', function (Blueprint $table) {
                if (Schema::hasColumn('memory_snap_games', 'image_started_at')) {
                    $table->dropColumn('image_started_at');
                }
                if (Schema::hasColumn('memory_snap_games', 'next_game_url')) {
                    $table->dropColumn('next_game_url');
                }
            });
        }
    }
};
