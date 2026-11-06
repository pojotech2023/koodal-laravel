<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_jadhagams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('disk', 30)->default('public');
            $table->string('mime_type', 60);
            $table->unsignedInteger('size_bytes');
            $table->string('approval_status', 20)->default('pending'); // pending, approved, rejected
            $table->string('rejection_reason')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'approval_status']);
            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_jadhagams');
    }
};
