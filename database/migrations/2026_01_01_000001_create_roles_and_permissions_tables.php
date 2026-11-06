<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();     // super_admin, admin, moderator, support, user
            $table->string('label', 80);
            $table->text('description')->nullable();

            // Blocks deletion of the built-in roles from the admin UI. Removing
            // `user` or `super_admin` would brick the platform.
            $table->boolean('is_system')->default(false);

            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();     // users.suspend, payments.refund, …
            $table->string('group', 50);              // groups the checkboxes in the admin UI
            $table->string('label', 120);
            $table->timestamps();

            $table->index('group');
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();

            $table->primary(['permission_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
