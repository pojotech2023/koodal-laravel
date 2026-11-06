<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_fcm_tokens')) {
            Schema::create('user_fcm_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('fcm_token', 500)->unique();
                $table->string('device_type', 20)->default('android'); // android, ios
                $table->string('device_id', 191)->nullable();
                $table->string('app_version', 50)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('device_type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_fcm_tokens');
    }
};
