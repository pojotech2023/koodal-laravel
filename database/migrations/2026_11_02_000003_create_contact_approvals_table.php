<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contact_approvals')) {
            Schema::create('contact_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
                $table->string('status', 20)->default('pending'); // pending, approved, rejected
                $table->timestamps();

                $table->unique(['requester_id', 'owner_id']);
                $table->index(['owner_id', 'status']);
                $table->index(['requester_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_approvals');
    }
};
