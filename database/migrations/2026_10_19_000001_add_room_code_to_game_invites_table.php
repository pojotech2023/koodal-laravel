<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An invite used to find "its" room by guessing from the pair and the game
     * type, which breaks the moment a pair has two rooms of one type. The API
     * stores the room it created instead. Nullable and unread by the website,
     * so web invites keep working exactly as before.
     */
    public function up(): void
    {
        Schema::table('game_invites', function (Blueprint $table) {
            $table->string('room_code', 36)->nullable()->after('game_type');
        });
    }

    public function down(): void
    {
        Schema::table('game_invites', function (Blueprint $table) {
            $table->dropColumn('room_code');
        });
    }
};
