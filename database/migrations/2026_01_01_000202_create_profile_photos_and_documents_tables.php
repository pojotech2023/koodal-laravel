<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('path');
            $table->string('thumbnail_path')->nullable();

            // Server-generated blurred derivative for gated viewers. A CSS blur
            // still ships the real pixels to the client.
            $table->string('blurred_path')->nullable();

            $table->string('disk', 30)->default('public');
            $table->string('mime_type', 60);
            $table->unsignedInteger('size_bytes');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();

            $table->boolean('is_primary')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(0);

            $table->string('approval_status', 20)->default('pending');
            $table->string('rejection_reason')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'sort_order']);
            $table->index('approval_status');
        });

        // Deferred until profile_photos exists.
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->foreign('primary_photo_id')->references('id')->on('profile_photos')->nullOnDelete();
        });

        Schema::create('verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('type', 30);        // aadhaar, pan, passport, driving_licence, other

            // Hashed: we need to detect the same document submitted twice
            // without ever storing a government ID number in readable form.
            $table->string('document_number_hash')->nullable();

            // Private disk only. No public URL is generated for this table
            // anywhere in the application.
            $table->string('path');
            $table->string('disk', 30)->default('private');
            $table->string('mime_type', 60);
            $table->unsignedInteger('size_bytes');

            $table->string('status', 20)->default('pending');   // pending, approved, rejected, resubmit
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('remarks')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('document_number_hash');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropForeign(['primary_photo_id']);
        });

        Schema::dropIfExists('verification_documents');
        Schema::dropIfExists('profile_photos');
    }
};
