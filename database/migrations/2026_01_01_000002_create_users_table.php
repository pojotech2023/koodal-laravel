<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // The identifier users quote to support and search for. Generated
            // once at registration and never reused, even after deletion.
            $table->string('profile_code', 16)->unique();

            $table->string('name', 120);
            $table->string('email', 190)->nullable()->unique();

            // Mobile is the primary login handle in this market, so it is
            // required and unique while email is optional.
            $table->string('mobile', 16)->unique();

            $table->string('password');

            // Lives on `users`, not `user_profiles`: every search inverts on
            // gender, so it sits in the hottest WHERE clause in the system and
            // must not require a join.
            $table->string('gender', 10);

            $table->date('date_of_birth');

            $table->foreignId('role_id')->constrained()->restrictOnDelete();

            $table->string('status', 20)->default('pending');   // pending, active, suspended, deactivated, deleted
            $table->string('suspension_reason')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('mobile_verified_at')->nullable();

            // Powers the "recently active" sort and presence fallback.
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('last_login_at')->nullable();

            $table->string('registration_source', 20)->default('web');  // web, android, ios
            $table->string('referral_code', 20)->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'gender']);
            $table->index('last_active_at');
            $table->index('date_of_birth');
            $table->index('created_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 190);        // mobile or email
            $table->string('channel', 10);            // sms, email
            $table->string('purpose', 30);            // registration, login, password_reset, mobile_change

            // Hashed, never stored in the clear — an OTP is a credential.
            $table->string('code_hash');

            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['identifier', 'purpose', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
