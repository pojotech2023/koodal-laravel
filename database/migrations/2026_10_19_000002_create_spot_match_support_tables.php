<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `spot_matches` shipped without the three tables its models, MatchService
     * and InvitationService all read and write, so no match could ever be
     * created. Columns are exactly what those classes use.
     */
    public function up(): void
    {
        Schema::create('match_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('spot_matches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('invited_email')->nullable();
            $table->string('status', 20)->default('invited'); // invited, joined, ready
            $table->integer('score')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('wrong_count')->default(0);
            $table->timestamp('last_claim_at')->nullable();
            $table->timestamps();

            $table->unique(['match_id', 'user_id']);
        });

        Schema::create('match_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('spot_matches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('difference_id')->nullable()->constrained('puzzle_differences')->nullOnDelete();
            $table->decimal('x_pct', 8, 4);
            $table->decimal('y_pct', 8, 4);
            $table->boolean('is_correct')->default(false);
            $table->integer('points')->default(0);
            $table->timestamps();

            // The race arbiter: two players tapping the same difference in the
            // same instant cannot both be awarded it. NULLs (wrong taps) never
            // collide in a unique index.
            $table->unique(['match_id', 'difference_id']);
            $table->index(['match_id', 'user_id']);
        });

        Schema::create('match_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('spot_matches')->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_invitations');
        Schema::dropIfExists('match_claims');
        Schema::dropIfExists('match_players');
    }
};
